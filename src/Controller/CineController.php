<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CineController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
        private LoggerInterface $logger,

    )
    {
    }

    #[Route('/api/asciicasts', name: 'app_upload')]
    public function upload(Request $request,
                           string $cineCode='test'): Response
    {
        $this->logger->warning(json_encode($request->request->all()));
        $this->logger->warning(json_encode($request->getContent()));
        dump($request->files);
        if ($file = $request->files->get('asciicast')) {
            dump(file: $file->getContents()); // , name: $file->getClientOriginalName());
        } else {
            dump($request->files);
        }

        return new JsonResponse([
            'status' => 'okay',
            'url' => 'https://showcase.wip',
        ]);
    }
    #[Route('/player/{cineCode}', name: 'app_player')]
    public function cinePlayer(string $cineCode='test'): Response
    {
        $filename = $cineCode . '.cast';
        $asciiCast = file_get_contents($this->projectDir . '/public/' . $filename);
        return $this->render('cine.html.twig', [
            'asciiCast' => $asciiCast,
            'castCode' => $cineCode,
            'filename' => $filename,
        ]);

    }

    #[Route('/cine/{cineCode}', name: 'app_cine')]
    public function cineJson(string $cineCode): Response
    {
        $filename = $this->projectDir . '/public/' . $cineCode . '.cast';
        $clean = $this->cleanup($filename);
        $json = json_encode($clean, JSON_UNESCAPED_UNICODE); //  + JSON_UNESCAPED_SLASHES);
        return new Response($json, Response::HTTP_OK, ['Content-Type' => 'application/json']);
//        $header = json_encode([
//            'version' => 2,
//            'width' => 180,
//            'height' => 30
//        ]);
//        $data = [
//            $header
//        ];
//        foreach ($data as $jsonData) {
//            $x[] = json_encode($jsonData);
//        }
        return new Response(join("\n", $x));

        return $this->json(json_encode());

    }

    private function cleanup(string $cast): array {
        $response = [
            'header' => null,
            'markers' => [],
            'lines' => [],
        ];

        $isCapturingCommand = false;
        $currentCommand = '';
        $currentOutput = '';
        $inputStartTime = 0.0;
        $totalTime = 0.1;

        foreach (file($cast, FILE_IGNORE_NEW_LINES) as $index => $line) {
            if ($index === 0) {
                $response['header'] = json_decode($line, true);
                continue;
            }

            [$interval, $type, $text] = json_decode($line, true);
            $lineData = [
                'interval' => $interval,
                'type' => $type,
            ];

            if ($type === 'o') {
                if (str_ends_with($text, '$ ')) {
                    // Start capturing the command
                    $isCapturingCommand = true;
                    continue;
                }

                if ($isCapturingCommand) {
                    if (str_starts_with($text, "\r\n")) {
                        // End of the command
                        $inputStartTime = $totalTime;
                        $response['markers'][] = [$inputStartTime, $currentCommand];

                        $lineData['text'] = $currentOutput;
                        $response['lines'][] = [
                            'interval' => $inputStartTime,
                            'type' => 'o',
                            'text' => $currentCommand . "\r\n" //. $currentOutput,
                        ];

                        $response['lines'][] = $lineData;

                        $currentCommand = '';
                        $currentOutput = '';
                        $isCapturingCommand = false;
                    } else {
                        $currentCommand .= $text;
                    }
                } else {
                    // Capture regular output
                    $currentOutput = $text;
                    $lineData['text'] = $currentOutput;
                    $response['lines'][] = $lineData;
                }
            }

            $totalTime += $interval;
        }

        return $response;
    }

}
