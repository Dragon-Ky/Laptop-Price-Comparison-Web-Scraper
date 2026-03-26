<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat với AI </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="/public/images/logo_icon.png">
        
    <link rel="shortcut icon" href="/public/images/logo_icon.png">
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .chat-container {
            -ms-overflow-style: none;
            scrollbar-width: none;
            scroll-behavior: smooth;
        }
        /* Hiệu ứng dấu chấm động (Typing indicator) */
        .typing-dot { animation: typing 1.4s infinite ease-in-out both; }
        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }
        @keyframes typing {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        /* Animation hiện tin nhắn */
        .animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-100 overflow-hidden"> 
    <div class="flex h-screen w-full"> 
        
        <?php 
            // Load Sidebar
            $active_page = 'Dashboard';
            if (defined('BASE') || file_exists(dirname(dirname(__FILE__)) . '/includes/sidebar.php')) {
                define('BASE', dirname(dirname(__FILE__)));
                require_once BASE . '/includes/sidebar.php';
            }
        ?>

        <main class="flex-1 ml-64 flex flex-col h-full bg-white relative">
            
            <div class="bg-blue-600 p-4 flex items-center shadow-md z-10 shrink-0">
                <div class="bg-white p-2 rounded-full h-10 w-10 flex items-center justify-center text-blue-600">
                    <i class="fa-solid fa-robot text-xl"></i>
                </div>
                <div class="ml-3 text-white">
                    <h3 class="font-bold text-lg">Trợ lý AI</h3>
                    <p class="text-xs text-blue-100 flex items-center gap-1">
                        <span class="w-2 h-2 bg-green-400 rounded-full block"></span> Đang hoạt động
                    </p>
                </div>
                
                <button onclick="clearHistory()" class="ml-auto text-white hover:text-red-200 transition-colors" title="Xóa lịch sử trò chuyện">
                    <i class="fa-solid fa-trash-can text-lg"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 chat-container" id="chatBox">
                </div>

            <div id="loadingIndicator" class="hidden px-4 pb-2">
                <div class="flex items-center space-x-2 bg-gray-200 w-fit p-3 rounded-xl">
                    <div class="w-2 h-2 bg-gray-500 rounded-full typing-dot"></div>
                    <div class="w-2 h-2 bg-gray-500 rounded-full typing-dot"></div>
                    <div class="w-2 h-2 bg-gray-500 rounded-full typing-dot"></div>
                </div>
            </div>

            <div class="bg-white border-t border-gray-200 p-4 shrink-0">
                <form id="chatForm" class="flex items-center gap-2">
                    <button type="button" class="p-2 text-gray-500 rounded-lg cursor-pointer hover:text-gray-900 hover:bg-gray-100">
                        <i class="fa-solid fa-image text-lg"></i>
                    </button>
                    
                    <input type="text" id="chatInput" autocomplete="off" class="block mx-4 p-2.5 w-full text-sm text-gray-900 bg-white rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 outline-none shadow-sm" placeholder="Hỏi về giá laptop, cấu hình...">
                    
                    <button type="submit" class="inline-flex justify-center p-2 text-blue-600 rounded-full cursor-pointer hover:bg-blue-100 transition-colors">
                        <i class="fa-solid fa-paper-plane text-lg"></i>
                    </button>
                </form>
            </div>

        </main>
    </div>     
    <script src="../../controllers/Chat_box/chatbot.js"></script>
</body>
</html>