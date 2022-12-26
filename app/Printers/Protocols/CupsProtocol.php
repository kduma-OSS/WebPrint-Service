<?php

namespace App\Printers\Protocols;

use App\Api\JobModel;
use App\Exceptions\PrintFailedException;
use App\Exceptions\TypeNotSupportedException;

class CupsProtocol implements PrinterProtocolInterface
{
    public function supportedTypes(): array
    {
        return ['raw', 'ppd'];
    }

    public function printJob(JobModel $job, array $protocol_options): void
    {
        $valid = $this->getLocalPrinters();
        if (count($valid) == 0) {
            throw new PrintFailedException("You do not have any printers installed on this system via CUPS. Check 'lpr -a'.");
        }

        if (array_search($protocol_options['host'], $valid, true) === false) {
            throw new PrintFailedException("'{$protocol_options['host']}' is not a printer on this system. Printers are: [".implode(', ', $valid).']');
        }

        $options = collect();
        switch ($job->type) {
            case 'raw':
                $options['raw'] = null;
                break;

            case 'ppd':
                $options = collect($job->options)
                    ->filter(fn ($value) => ! is_bool($value) || $value === true)
                    ->map(fn ($value) => is_bool($value) ? null : $value);
                break;

            default:
                throw new TypeNotSupportedException();
        }

        // Build command to work on data
        $tmpfname = tempnam(sys_get_temp_dir(), 'print-');
        if ($tmpfname === false) {
            throw new PrintFailedException('Failed to create temp file for printing.');
        }
        file_put_contents($tmpfname, $job->content);

        $cmd = sprintf(
            'lp -d %s %s %s',
            escapeshellarg($protocol_options['host']),
            $options->map(fn ($value, $key) => '-o '.escapeshellarg($value !== null ? $key.'='.$value : $key))->implode(' '),
            escapeshellarg($tmpfname)
        );
        try {
            $this->getCmdOutput($cmd);
        } catch (PrintFailedException $e) {
            unlink($tmpfname);
            throw $e;
        }
        unlink($tmpfname);
    }

    /**
     * Load a list of CUPS printers.
     *
     * @return array A list of printer names installed on this system. Any item
     *  on this list is valid for constructing a printer.
     */
    protected function getLocalPrinters()
    {
        $outpStr = $this->getCmdOutput('lpstat -a');
        $outpLines = explode("\n", trim($outpStr));
        foreach ($outpLines as $line) {
            $ret[] = $this->chopLpstatLine($line);
        }

        return $ret;
    }

    /**
     * Run a command and throw an exception if it fails, or return the output if it works.
     * (Basically exec() with good error handling)
     *
     * @param  string  $cmd Command to run
     */
    protected function getCmdOutput($cmd)
    {
        $descriptors = [
            1 => [
                'pipe',
                'w',
            ],
            2 => [
                'pipe',
                'w',
            ],
        ];
        $process = proc_open($cmd, $descriptors, $fd);
        if (! is_resource($process)) {
            throw new PrintFailedException("Command '$cmd' failed to start.");
        }
        /* Read stdout */
        $outputStr = stream_get_contents($fd[1]);
        fclose($fd[1]);
        /* Read stderr */
        $errorStr = stream_get_contents($fd[2]);
        fclose($fd[2]);
        /* Finish up */
        $retval = proc_close($process);
        if ($retval != 0) {
            throw new PrintFailedException("Command $cmd failed: $errorStr");
        }

        return $outputStr;
    }

    /**
     * Get the item before the first space in a string
     *
     * @param  string  $line
     * @return string the string, up to the first space, or the whole string if it contains no spaces.
     */
    private function chopLpstatLine($line)
    {
        if (($pos = strpos($line, ' ')) === false) {
            return $line;
        } else {
            return substr($line, 0, $pos);
        }
    }
}
