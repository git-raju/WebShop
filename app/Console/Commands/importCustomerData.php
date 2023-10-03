<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\ImportLog;

class importCustomerData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:customer-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import customer data from localy stored CSV file';

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
        Customer::truncate(); // Flush the customers table data  (optional)
        $this->info('Reading customers CSV data...');
        // Read the localy stored CSV file
        $csvData = fopen(base_path('database/csv/customers.csv'), 'r');
        $headerRow = true; // Exclude header row of CSV file
        $this->info('Importing CSV data...');
        $insertCount = 0;
        $insertFailedCount = 0;

        while (($data = fgetcsv($csvData, 555, ',')) !== false) {
            if (!$headerRow) {
                $newCustomer = Customer::create([
                    'job_title'          => $data['1'],
                    'email'              => $data['2'],
                    'firstName_lastName' => $data['3'],
                    'phone'              => $data['5'],
                    'registered_since'   => $this->getTimestampFromString($data['4'])
                ]);
                if($newCustomer){
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
            'import_item'           => 'Customers',
            'total_imported'        => $insertCount,
            'total_import_failed'   => $insertFailedCount,
            'created_at'            => date('Y-m-d H:i:s')
        ]);
        $this->info('Imported successfully...');
        return 0;
    }

    private function getTimestampFromString($stringDateTime){
        // Create a DateTime object from the original date string
        $dateTime = \DateTime::createFromFormat('l, F j, Y', $stringDateTime);

        // Format the DateTime object into YYYY-mm-dd H:i:s
        $formattedDate = $dateTime->format('Y-m-d H:i:s');

        // Output the formatted date
        return $formattedDate;
    }
}
