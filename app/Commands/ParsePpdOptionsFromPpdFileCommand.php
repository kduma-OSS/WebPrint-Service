<?php

namespace App\Commands;

use Brick\VarExporter\VarExporter;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ParsePpdOptionsFromPpdFileCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'ppd:parse {file} {---array}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Parses PPD file and outputs json with ppd_options';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $contents = array_map('trim', file($this->argument('file')));
        $options_array = [];
        $currentGroup = ['General', 'General'];
        $order = 1;

        foreach ($contents as $key => $value) {
            if (!preg_match('/^\\*([a-zA-Z0-9-_]+)/u', $value, $cmd))
                continue;

            if($cmd[1] == 'OpenUI' && preg_match('/^\\*([a-zA-Z0-9-_]+) \\*([^\\/]+)\\/([^:]+): ([^\\n\\r]+)/um', $value, $openUI)){
                if($openUI[2] == 'PageRegion')
                    continue;

                if(!isset($options_array[$openUI[2]]))
                    $options_array[$openUI[2]] = [
                        'name' => $openUI[3],
                        'values' => [],
                        'default' => null,
                        'enabled' => true,
                        'order' => $order++,
                        'group_key' => $currentGroup[0],
                        'group_name' => $currentGroup[1],
                    ];
            }


            if(preg_match('/^\\*Default([a-zA-Z0-9-_]+): ([^\\n]+)/um', $value, $Default)){
                if(isset($options_array[$Default[1]])) {
                    $options_array[$Default[1]]['default'] = $Default[2];
                }
            }

            if (in_array($cmd[1], array_keys($options_array))) {
                preg_match('/^\\*([a-zA-Z0-9-_]+) ([^\\/]+)\\/([^:\\n\\r]+):/u', $value, $opt_val);
                $options_array[$opt_val[1]]['values'][$opt_val[2]] = $opt_val[3];
            }


            if($cmd[1] == 'OpenGroup'){
                preg_match('/^\\*([a-zA-Z0-9-_]+): ([a-zA-Z0-9-_]+)\/([^\\n]+)/um', $value, $group);
                $currentGroup = [$group[2], $group[3]];
            }


            if($cmd[1] == 'CloseGroup'){
                preg_match('/^\\*([a-zA-Z0-9-_]+): ([^\\n]+)/um', $value, $group);
                $currentGroup = ['General', 'General'];
            }

        }

        foreach ($options_array as $index => $option) {
            $new_values = [];
            $order = 1;
            foreach ($option['values'] as $key => $value) {
                $new_values[$key] = [
                    'name' => $value,
                    'order' => $order++,
                    'enabled' => true,
                ];
            }
            $options_array[$index]['values'] = $new_values;
        }

        if($this->option('array')){
            echo VarExporter::export($options_array);
        } else {
            echo json_encode($options_array, JSON_PRETTY_PRINT);
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
}
