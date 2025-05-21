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

    private function cleanup(string $cast): array
    {
        $response = [];
        $inInput = false;
        $inputText = '';
        $outputLine = '';
        $totalTime = 0.1;

        // $line is a tuple
        foreach (file($cast, FILE_IGNORE_NEW_LINES) as $idx => $line) {
            if ($idx === 0) {
                $header = json_decode($line);
                $response['header'] = $header;
                $response['markers'] = [];
            } else {
                [$interval, $type, $text] = json_decode($line);
                $lineData = [
                    'interval' => $interval,
                    'type' => $type,
                ];
                // v2 is absolute, we need to create a relative time


                if ($type === 'o') { // why not 'i'?
                    // probably won't work with other terminals, it's a hack we can make work for the moment
                    if (str_ends_with($text, '$ ')) {
                        $inInput = true;
                        continue;
                    }
//                    dump($text, $inInput);
                    if ($inInput)
                    {
                        // if it's the crlf at the end of the command, consolidate
                        if (str_starts_with($text, "\r\n")) {
                            $inputStartTime = $totalTime;
                            // hack, should be a method that handles this more elegantly.

                            $lineData['text'] = $outputLine;
                            if ($inputText) {
                                $response['markers'][] = [$inputStartTime, $inputText];
//                                    'timestamp' => $totalTime,
//                                    'label' => $inputText,
//                                ];
//                                $response['lines'][] = [
//                                    'interval' => $totalTime, // not interval, markers are absolute
//                                    'type' => 'm',
//                                    'text' => $inputText,
//                                ];
                            }
                            $outputLine = $text;


                            // we're at the end of an input command
//                            $response['lines'][] = $inputText;
                            $inInput = false;
                        } else {
                            $inputText .= $text;
//                            dump(inputText: $inputText, new: $text);
//                            continue;
                        }
                    } else {
                        $outputLine = $text;
//                        $response['lines'][] = $text;
                        // this should be output
                    }
                }
                if ($outputLine) {
                    $totalTime += $interval;
                    $lineData['text'] = $outputLine;
                    $response['lines'][] = $lineData;
                }
            }
        }
//        dd($cast, $response['lines']);
        return $response;
        // v2 format

    }
}
