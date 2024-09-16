<?php
require __DIR__ . '/vendor/autoload.php';

use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Dompdf\Dompdf;

$loader = new FilesystemLoader(__DIR__ . '/src/View');
$twig = new Environment($loader);

$server = new Server("127.0.0.1", 9502);

$server->on("request", function (Request $request, Response $response) use ($twig) {
    // Check content type and parse accordingly
    if ($request->header['content-type'] === 'application/json') {
        $postData = json_decode($request->rawContent(), true); // Handle JSON data
    } else {
        $postData = $request->post; 
    }

    if (!isset($postData['template']) || !isset($postData['data'])) {
        $response->status(400); // Bad request
        $response->end('Invalid request. Missing template or data.');
        return;
    }

    $templateName = $postData['template'];
    $data = json_decode($postData['data'], true);

    // Load the template
    try {
        $template = $twig->load($templateName);
        $html = $template->render($data);

        // Generate PDF
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $fileName = 'receipt_' . time() . '.pdf';
        $outputPath = __DIR__ . '/output/' . $fileName;
        file_put_contents($outputPath, $dompdf->output());

        // Set up a timer to delete the file after 24 hours
        $deleteTime = time() + 24 * 3600;
        swoole_timer_after(24 * 3600 * 1000, function() use ($outputPath) {
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }
        });

        $response->status(200); // Success
        $response->end(json_encode([
            'message' => "PDF generated: " . $fileName,
            'file_path' => $outputPath
        ]));

    } catch (Exception $e) {
        $response->status(500); 
        $response->end("Error: " . $e->getMessage());
    }
});

$server->start();
