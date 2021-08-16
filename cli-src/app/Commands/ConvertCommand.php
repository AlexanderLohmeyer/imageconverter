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
    protected $signature = 'convert {source : the input path of the image} { output : the output path of the image } { max-width } { max-height } { quality }';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Command description';

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


        if (is_dir($source)) {
            $this->info('Directory Mode');
            $files = scandir($source);

            $successCounter = 0;
            $errorCounter = 0;
            $skippedCounter = 0;

            foreach ($files as $file) {
                if (is_dir($file)) continue;
                $fileInfo = pathinfo($file);
                $fileExtension = $fileInfo['extension'];
                if (! in_array($fileExtension, $this->SUPPORTED_FORMATS)) {
                    $this->warn($file . ': Skipped file because File Extension ' . $fileExtension . ' is not supported.');
                    $skippedCounter++;
                    continue;
                }
                $inputFile = $source . DIRECTORY_SEPARATOR . $file;
                $outputFile = $output . DIRECTORY_SEPARATOR . $fileInfo['filename'] . $this->OUTPUT_EXTENSION;
                if ($this->convertToJpeg($inputFile, $outputFile, $quality)) {
                    $successCounter++;
                } else {
                    $this->error($file . ': Unknown Error while Converting File');
                    $errorCounter++;
                }
            }
            $this->info('Converted Files: ' . $successCounter . ' | Skipped Files: ' . $skippedCounter . ' | Failed Files: ' . $errorCounter);
        } else {
            $this->info('Single File Mode');
            $fileExtension = $this->getFileExtension($source);

            if (! in_array($fileExtension, $this->SUPPORTED_FORMATS)) {
                $this->error($source . ': File Extension ' . $fileExtension .' is not supported.');
                return;
            }

            if ($this->convertToJpeg($source, $output, $quality)) {
                $this->info('Successful converted Image');
            } else {
                $this->error('Could not convert image');
            }
        }
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

    private function convertToJpeg(string $source, string $output, $quality): ?bool {
        $fileExtension = $this->getFileExtension($source);

        switch ($fileExtension) {
            case 'png':
                $image = imagecreatefrompng($source);
                break;
            case 'gif':
                $image=imagecreatefromgif($source);
                break;
            default:
                return false;
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
}
