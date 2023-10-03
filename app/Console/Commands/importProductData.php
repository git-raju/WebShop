<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\ImportLog;

class importProductData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:product-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import product data from localy stored CSV file';

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
        Product::truncate(); // Flush the product table data  (optional)
        $this->info('Reading products CSV data...');
        // Read the localy stored CSV file
        $csvData = fopen(base_path('database/csv/products.csv'), 'r');
        $headerRow = true; // Exclude header row of CSV file
        $this->info('Importing CSV data...');
        $insertCount = 0;
        $insertFailedCount = 0;
        while (($data = fgetcsv($csvData, 555, ',')) !== false) {
            if (!$headerRow) {
                $newProduct = Product::create([
                    'product_name'  => $data['1'],
                    'price'         => $data['2']
                ]);
                if($newProduct){
                    $insertCount++;
                }  
                else{
                    $insertFailedCount++;
                }
            }
            $headerRow = false;
        }
        fclose($csvData);
        ImportLog::create([
            'import_item'           => 'Products',
            'total_imported'        => $insertCount,
            'total_import_failed'   => $insertFailedCount,
            'created_at'            => date('Y-m-d H:i:s')
        ]);
        $this->info('Imported successfully...');
        return 0;
    }
}
