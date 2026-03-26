/**
 * chatbot.js
 * Phiên bản: Hỗ trợ hiển thị HTML Card sản phẩm từ PHP
 */

// --- CẤU HÌNH ---
const scriptPath = document.currentScript.src;
// Sửa tên file PHP cho đúng với file bạn đang dùng
const API_URL = scriptPath.replace('chatbot.js', 'Chat_box_controler.php'); 

console.log("Đang gọi API tại:", API_URL);

// Các biến DOM
const chatBox = document.getElementById('chatBox');
const chatInput = document.getElementById('chatInput');
const chatForm = document.getElementById('chatForm');
const loadingIndicator = document.getElementById('loadingIndicator');

// Hàm xử lý Link cho tin nhắn của User (AI không dùng cái này nữa)
function formatText(text) {
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    return text.replace(urlRegex, function(url) {
        return `<a href="${url}" target="_blank" style="color: #0066cc; text-decoration: underline;">${url}</a>`;
    });
}

// 1. Hàm vẽ tin nhắn (ĐÃ SỬA LOGIC HIỂN THỊ)
function appendMessage(sender, text, time = null) {
    if (!time) {
        const now = new Date();
        time = now.getHours() + ":" + String(now.getMinutes()).padStart(2, '0');
    }

    let contentHtml = '';

    if (sender === 'user') {
        // Tin nhắn người dùng: Vẫn xử lý link và xuống dòng bình thường
        contentHtml = `<p class="text-sm font-normal whitespace-pre-wrap">${formatText(text)}</p>`;
        
        var html = `
        <div class="flex items-start gap-2.5 flex-row-reverse animate-fade-in">
            <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 flex-shrink-0"><i class="fa-solid fa-user text-sm"></i></div>
            <div class="flex flex-col gap-1 w-full max-w-[85%]">
                <div class="flex items-center justify-end space-x-2 rtl:space-x-reverse"><span class="text-sm font-normal text-gray-500">${time}</span><span class="text-sm font-semibold text-gray-900">Bạn</span></div>
                <div class="flex flex-col leading-1.5 p-4 border border-blue-300 bg-white text-black rounded-s-xl rounded-ee-xl shadow-sm">
                    ${contentHtml}
                </div>
            </div>
        </div>`;
    } else {
        // Tin nhắn AI: HIỂN THỊ RAW HTML (Để hiện được ảnh và thẻ Card)
        // Không dùng formatText() để tránh làm hỏng mã HTML từ PHP gửi sang
        contentHtml = `<div class="text-sm font-normal text-gray-900 w-full overflow-hidden">${text}</div>`;

        var html = `
        <div class="flex items-start gap-2.5 animate-fade-in">
            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 flex-shrink-0"><i class="fa-solid fa-robot text-sm"></i></div>
            <div class="flex flex-col gap-1 w-full max-w-[95%]"> 
                <div class="flex items-center space-x-2 rtl:space-x-reverse"><span class="text-sm font-semibold text-gray-900">AI Tư Vấn</span><span class="text-sm font-normal text-gray-500">${time}</span></div>
                <div class="flex flex-col leading-1.5 p-4 border border-gray-200 bg-white rounded-e-xl rounded-es-xl shadow-sm">
                    ${contentHtml}
                </div>
            </div>
        </div>`;
    }

    chatBox.insertAdjacentHTML('beforeend', html);
    chatBox.scrollTop = chatBox.scrollHeight;
}

// 2. Load lịch sử
async function loadHistory() {
    try {
        const res = await fetch(API_URL, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'fetch' })
        });
        const result = await res.json();
        if (result.status === 'success') {
            chatBox.innerHTML = ''; 
            result.data.forEach(msg => appendMessage(msg.sender, msg.message, msg.created_at));
            if(result.data.length === 0) appendMessage('ai', 'Chào bạn! Bạn cần tìm laptop loại nào? (Ví dụ: Laptop Dell gaming dưới 20 triệu)');
        }
    } catch (error) {
        console.error('Lỗi load history:', error);
    }
}

// 3. Gửi tin nhắn
chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const message = chatInput.value.trim();
    if (!message) return;

    appendMessage('user', message);
    chatInput.value = '';
    loadingIndicator.classList.remove('hidden');
    chatBox.scrollTop = chatBox.scrollHeight;

    try {
        const res = await fetch(API_URL, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'send', message: message })
        });
        const result = await res.json();
        loadingIndicator.classList.add('hidden');

        if (result.status === 'success') {
            appendMessage('ai', result.reply);
        } else {
            appendMessage('ai', 'Lỗi: ' + result.message);
        }
    } catch (error) {
        loadingIndicator.classList.add('hidden');
        appendMessage('ai', 'Lỗi kết nối Server.');
    }
});

// 4. Xóa lịch sử chat
async function clearHistory() {
    if(!confirm('Bạn có chắc muốn xóa toàn bộ cuộc trò chuyện này không?')) return;

    try {
        const res = await fetch(API_URL, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'clear' }) 
        });
        const result = await res.json();
        
        if (result.status === 'success') {
            chatBox.innerHTML = ''; 
            appendMessage('ai', 'Đã xóa lịch sử. Bạn cần tìm máy gì?');
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error(error);
        alert('Không thể kết nối đến Server để xóa.');
    }
}

document.addEventListener('DOMContentLoaded', loadHistory);