$html = '
<ul class="icons-spec">
<li title="CPU"><span></span><span class="text-var">i5 1135G7 </span></li>
<li title="Ổ cứng"><span></span><span class="text-var">M2.SSD</span></li>
<li title="Ram"><span></span><span class="text-var">16GB 3200MHz DDR4</span></li>
<li title="Đồ họa"><span></span><span class="text-var">Intel® HD Graphics Family</span></li>
<li title="Màn hình"><span></span><span class="text-var">14 inch</span></li>
</ul>';
<?php
preg_match_all(
    '/<li[^>]*title="([^"]+)"[\s\S]*?<span class="text-var">\s*(.*?)\s*<\/span>/',
    $html,
    $matches,
    PREG_SET_ORDER
);

foreach ($matches as $m) {
    echo $m[1] . ' : ' . $m[2] . PHP_EOL;
}
?>