<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\ImportLog;

class importCustomerDataFromURL extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:customer-data-from-URL';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import customer data from CSV URL';

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

        $username = env('USERNAME_CSV');
        $password = env('PASSWORD_CSV');
        // Read CSV data through cURL call
        $curl = curl_init(env('CUSTOMER_CSV_URL', 'https://backend-developer.view.agentur-loop.com/customers.csv'));        
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $csvContent = curl_exec($curl);
        if (curl_errno($curl)) {
            die('cURL Error: ' . curl_error($curl));
        }
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // Check if the request was successful
        if ($httpStatus !== 200) {
            die('HTTP Request failed with status ' . $httpStatus);
        }        
        curl_close($curl);

        // Store the CSV data
        $csvData = [];
        // Parse the CSV content into an array
        $lines = explode("\n", $csvContent);
        $headerRow = true; // Exclude header row of CSV file
        foreach ($lines as $line) {
            if (!$headerRow) {
                $csvData[] = str_getcsv($line);
            }
            $headerRow = false;
        }
        // Now loop through $csvData
        $this->info('Importing CSV data...');
        $insertCount = 0;
        $insertFailedCount = 0;
        foreach ($csvData as $row) {             
            $newCustomer = Customer::create([
                'job_title'          => $row[1],                
                'email'              => $row[2],
                'firstName_lastName' => $row[3],
                'phone'              => $row[5],
                'registered_since'   => $this->getTimestampFromString($row[4])                
            ]);     
            if($newCustomer){
                $insertCount++;
            }  
            else{
                $insertFailedCount++;
            }   
        }   
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
