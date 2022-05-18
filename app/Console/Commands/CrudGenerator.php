<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CrudGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:gen {name : Class (singular) for example User}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Crud including Model, Controller, Views';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');

        $this->controller($name);
        $this->model($name);
        $this->request($name);
        $this->views($name);

        $file = file_get_contents(base_path("routes/web.php"));
        $file = str_replace("<?php", "<?php\nuse \App\Http\Controllers\\".$name."Controller;", $file);
        
        file_put_contents(base_path("routes/web.php"), $file);
        File::append(base_path('routes/web.php'), "\n".'Route::resource(\'' . Str::plural(strtolower($name)) . "', {$name}Controller::class);");

        $this->info('Generating Migration File...');
        Artisan::call('make:migration create_' . Str::plural(strtolower($name)) . '_table --create=' . Str::plural(strtolower($name)));
        $this->info('CRUD generated successfully !!!');
    }

    private function getStub($type)
    {
        return file_get_contents(resource_path("stubs/$type.stub"));
    }

    protected function model($name)
    {
        $this->info('Generating Model...');

        $modelTemplate = str_replace(
            ['{{modelName}}'],
            [$name],
            $this->getStub('Model')
        );

        file_put_contents(app_path("/Models/{$name}.php"), $modelTemplate);
    }

    protected function controller($name)
    {
        $this->info('Generating Controller...');

        $controllerTemplate = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePluralLowerCase}}',
                '{{modelNameSingularLowerCase}}'
            ],
            [
                $name,
                strtolower(Str::plural($name)),
                strtolower($name)
            ],
            $this->getStub('Controller')
        );
    
        file_put_contents(app_path("/Http/Controllers/{$name}Controller.php"), $controllerTemplate);
    }

    protected function request($name)
    {
        $this->info('Generating Request...');

        $requestTemplate = str_replace(
            ['{{modelName}}'],
            [$name],
            $this->getStub('Request')
        );
    
        if(!file_exists($path = app_path('/Http/Requests')))
            mkdir($path, 0777, true);
    
        file_put_contents(app_path("/Http/Requests/{$name}Request.php"), $requestTemplate);
    }

    protected function views($name)
    {
        $this->info('Generating Views...');
        $name = Str::plural(strtolower($name));
        $path = resource_path("views/{$name}");
    
        if(!file_exists($path))
            mkdir($path, 0777, true);
        else
            return true;
        
        // Create empty View files
        $this->createView($path, 'create');
        $this->createView($path, 'edit');
        $this->createView($path, 'index');
        $this->createView($path, 'show');
        
    }

    protected function createView($path, $name)
    {
        file_put_contents($path . "/$name.blade.php", '');
        $this->info("Created $name file...");
    }

}
