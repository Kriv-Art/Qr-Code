# PHP Qr-Code Generator

PHP qrcode generator based on [Project Nayuki](https://www.nayuki.io/page/qr-code-generator-library).

## Usage

```php
use KrivArt\QrCode\Ecc;
use KrivArt\QrCode\QrCode;

require_once __DIR__ . '/vendor/autoload.php';

$text      = 'HELLOW';
$errCorLvl = new Ecc(Ecc::LOW);
$qr        = QrCode::encodeText($text, $errCorLvl);
$data      = $qr->toSvgString(4);

\file_put_contents('qrcode.svg', $data);
```