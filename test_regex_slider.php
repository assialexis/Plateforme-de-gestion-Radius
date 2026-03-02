<?php
$html = <<<HTML
        <!-- Image Slider -->
        <div id="slider-container" class="slider-container" style="display:none;">
            <div class="slider">
                <div class="slides">
                    <div class="slide active">
                        <img src="img/img1.jpg" alt="Slide 1">
                    </div>
                    <div class="slide">
                        <img src="img/img2.jpg" alt="Slide 2">
                    </div>
                </div>
                <div class="slider-dots">
                    <span class="dot active" onclick="goToSlide(0)"></span>
                    <span class="dot" onclick="goToSlide(1)"></span>
                </div>
            </div>
        </div>
HTML;

$slidesHtml = '<div class="slides">REPLACED</div>';
$dotsHtml = '<div class="slider-dots">REPLACED</div>';

$newHtml = preg_replace('/<div class="slider">.*?<\/div>\s*<\/div>/s', '<div class="slider">' . "\n                " . $slidesHtml . "\n                " . $dotsHtml . "\n            </div>\n        </div>", $html);

echo $newHtml;
