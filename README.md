# PHP Qr-Code Generator

PHP Qr-Code generator based on [Project Nayuki](https://www.nayuki.io/page/qr-code-generator-library).

## Usage

### Initializing QrCode class

```php
use KrivArt\QrCode\Ecc;
use KrivArt\QrCode\QrCode;

require_once __DIR__ . '/vendor/autoload.php';

$text      = 'Hello World';
$errCorLvl = new Ecc(Ecc::LOW);
$qr        = QrCode::encodeText($text, $errCorLvl);

$height = 512;
$width  = 512;
$border = 20;
$colors = [
    'backgroundColor' => '#ffffff',
    'foregroundColor' => '#000000'
];
```

### Output to Svg

```php
$data = $qr->toSvgString(4);

\file_put_contents('qrcode.svg', $data);
```
#### Or

```php
use KrivArt\QrCode\Output\Svg;

$svg = new Svg($qr);
$svg->output('qrcode.svg');
```

### Output to Png

```php
use KrivArt\QrCode\Output\Png;

$png = new Png($qr, $height, $width, $border, $colors);
$png->output('qrcode.png');
```

### Output to Jpeg

```php
use KrivArt\QrCode\Output\Jpeg;

$jpg = new Jpeg($qr $height, $width, $border, $colors);
$jpg->output('qrcode.jpg');
```
