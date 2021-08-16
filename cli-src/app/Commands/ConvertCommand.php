<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use mysql_xdevapi\Exception;

class ConvertCommand extends Command
{

    private $OUTPUT_EXTENSION = '.jpeg';
    private $SUPPORTED_FORMATS = [
        'png',
        'gif'
    ];


    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'convert {source : the input path of the image} { output : the output path of the image } { max-width } { max-height } { quality } { --json-output : Output as json-encoded-string}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Convert a single or a directory of Images';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $source = $this->argument('source');
        $output = $this->argument('output');
        $quality = $this->argument('quality');
        $maxWidth = $this->argument('max-width');
        $maxHeight = $this->argument('max-height');
        $json = $this->option('json-output');

        if (is_dir($source)) {
            $files = scandir($source);

            $successCounter = 0;
            $errorCounter = 0;
            $skippedCounter = 0;

            foreach ($files as $file) {
                if (is_dir($file)) continue;
                $fileInfo = pathinfo($file);
                $fileExtension = $fileInfo['extension'];
                if (! in_array($fileExtension, $this->SUPPORTED_FORMATS)) {
                    if (!$json) $this->warn($file . ': Skipped file because File Extension ' . $fileExtension . ' is not supported.');
                    $skippedCounter++;
                    continue;
                }
                $inputFile = $source . DIRECTORY_SEPARATOR . $file;
                $outputFile = $output . DIRECTORY_SEPARATOR . $fileInfo['filename'] . $this->OUTPUT_EXTENSION;
                if ($this->convertToJpeg($inputFile, $outputFile, $quality, $maxWidth, $maxHeight)) {
                    $successCounter++;
                } else {
                    if (! $json) $this->error($file . ': Unknown Error while Converting File');
                    $errorCounter++;
                }
            }
            if (! $json) { $this->info('Converted Files: ' . $successCounter . ' | Skipped Files: ' . $skippedCounter . ' | Failed Files: ' . $errorCounter); }
            else {
                $this->printJsonOutput($successCounter, $skippedCounter, $errorCounter);

            }
        } else {
            $fileExtension = $this->getFileExtension($source);

            if (! in_array($fileExtension, $this->SUPPORTED_FORMATS)) {
                if (! $json) { $this->error($source . ': File Extension ' . $fileExtension .' is not supported.'); }
                else {
                    $this->printJsonOutput(0, 1, 0);
                }
                return;
            }

            if ($this->convertToJpeg($source, $output, $quality, $maxWidth, $maxHeight)) {
                if(! $json) {
                    $this->info('Successful converted Image');
                } else {
                    $this->printJsonOutput(1, 0, 0);
                }
            } else {
                if (! $json) {
                    $this->error('Could not convert image');
                } else {
                    $this->printJsonOutput(0, 0, 1);
                }
            }
        }
    }

    private function printJsonOutput(int $success, int $skipped, int $error): void {
        $response = [
            'converted' => $success,
            'skipped' => $skipped,
            'error' => $error
        ];
        $this->info(json_encode($response));
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }


    private function getFileExtension(string $fileName): string {
        $fileNameArr = explode('.', $fileName);
        return strtolower(end($fileNameArr));
    }

    private function convertToJpeg(string $source, string $output, $quality, int $maxWidth, int $maxHeight): ?bool {
        $fileExtension = $this->getFileExtension($source);

        switch ($fileExtension) {
            case 'png':
                $image = imagecreatefrompng($source);
                break;
            case 'gif':
                $image = imagecreatefromgif($source);
                break;
            default:
                return false;
        }

        $resizedImg = $this->resizeIfNeeded($image, $maxWidth, $maxHeight);
        if ($resizedImg) {
            $image = $resizedImg;
        } else {
            $this->warn($source . ': Could not Resize this file. Proceed without Resizing.');
        }

        try {
            if ($image != null) {
                imagejpeg($image, $output, $quality);
                imagedestroy($image);
            }
        } catch (\Exception $exception) {
            return false;
        }
        return true;
    }

    private function resizeIfNeeded($image, int $maxWidth, int $maxHeight) {
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);
        if ($imageWidth > $maxWidth || $imageHeight > $maxHeight) {
            $aspectRatio = $imageWidth / $imageHeight;

            if ($maxHeight * $aspectRatio > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = $newWidth / $aspectRatio;
            } else {
                $newHeight = $maxHeight;
                $newWidth = $newHeight * $aspectRatio;
            }

            try {
                $newSizedImg = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($newSizedImg, $image, 0, 0, 0, 0, $newWidth, $newHeight, $imageWidth, $imageHeight);
            } catch (\Exception $exception) {
                return null;
            }


            return $newSizedImg;
        } else {
            return $image;
        }

    }
}
