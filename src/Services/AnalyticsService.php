<?php

namespace App\Services;

use App\Entity\Customer;
use App\Repository\ContractRepository;
use App\Repository\CustomerRepository;
use App\Repository\DevicesRepository;
use App\Repository\MothlyYieldRepository;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class AnalyticsService
{
    //constructor
    public function __construct
    (
        private CustomerRepository $customerRepository,
        private ContractRepository $contractRepository,
        private DevicesRepository $deviceRepository,
        private MothlyYieldRepository $monthlyYieldRepository,
    ){}

    public function CustomerOverview($timeframe = "m")
    {
        $timeframes =
        [
            'y' => 1,
            'q' => 4,
            'm' => 12
        ];

        $timeframe = $timeframes[$timeframe];

        $customers = $this->getCustomers();
        $contracts = $this->getContracts();
        $monthlyYields = $this->getMonthlyYields();

        $sortedData = $this->SortCustomerData($monthlyYields, $customers);

        //making new spreadsheet
        $fileName = './public/Spreadsheets/customerOverview.xlsx';
        $spreadsheet = new Spreadsheet($fileName);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customer Overview');

        $col = 'A';
        $row = 1;

        $this->setHeaders($sheet, $col, $row, $timeframe,reset($sortedData)[0]->getStartDate());

        $col = 'A';
        $row = 2;

        foreach($sortedData as $customerData)
        {
            $sheet->setCellValue($col.$row, $customerData[0]->getDevice()->getCustomer()->getFirstName().' '.$customerData[0]->getDevice()->getCustomer()->getLastName());
            $col++;
            //calc yearly revenue
            $sheet->setCellValue($col.$row, $this->calcYearlyRevenue($customerData));
            //
            $col = 'D';

            foreach($customerData as $data)
            {
                $sheet->setCellValue($col.$row,$data->getStartDate()->format('Y-m-d'));
                $col++;
            }
            $row++;
            $col = 'A';
        }
        
        foreach ($sheet->getColumnIterator() as $column)
        {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
                
        //save sheet
        $writer = IOFactory::createWriter($spreadsheet, "Xlsx");
        $writer->save($fileName);

        return 'Customer Overview spreadsheet created. check' . $fileName . ' for the results';
    }

    private function calcYearlyRevenue($customerData)
    {
        $yearlyRevenue = 0;

        foreach($customerData as $data)
        {
            $yearlyRevenue += $data->getYield();
        }

        return $yearlyRevenue; 
    }

    private function setHeaders($sheet, $col, $row, $timeframe, $startdate)
    {
        $newDate = new DateTime($startdate->format('Y-m-d'));
        $headers = [
            'Customers',
            'Yearly revenue',
            'Bought Kwh',
        ];     

        for($i = 0; $i < count($headers); $i++)
        {
            $sheet->setCellValue($col.$row, $headers[$i]);
            $sheet->getStyle($col.$row)->getFont()->setBold(true);
            $col++;
        }

        date_modify($newDate, '-1 month');

        for($i = 0; $i < $timeframe; $i++)
        {
            date_modify($newDate, '+1 month');
            $sheet->setCellValue($col.$row, $newDate->format('m-Y'));
            $sheet->getStyle($col.$row)->getFont()->setBold(true);
            $col++;
        }
    }

    private function SortCustomerData($yieldData,$customers)
    {
        $sortedData = [];

        foreach($customers as $customer)
        {
            $customerData = [];

            foreach($yieldData as $key => $value)
            {
                if($value->getDevice()->getCustomer()->getID() == $customer->getID())
                {
                    $customerData[] = $value;     
                    unset($yieldData[$key]);
                }
            }

            $customerData = $this->mergeOnDate($customerData);

            usort($customerData, function($a, $b) 
            {
                return $a->getStartDate() <=> $b->getStartDate();
            });
            
            $sortedData[$customer->getID()] = $customerData;
        }

        return $sortedData;
    }

    private function mergeOnDate($data)
    {
        $mergedData = [];

        foreach($data as $checkKey => $checkValue)
        {
            $newValue = true;

            foreach($mergedData as $compairKey => $compairValue)
            {
                if($checkValue->getStartDate()->format('Y-m-d') == $compairValue->getStartDate()->format('Y-m-d'))
                {
                    $compairValue->setYield($compairValue->getYield() + $checkValue->getYield());
                    $newValue = false;
                    continue;
                }
            }

            if($newValue == true)
            {
                $mergedData[] = $checkValue;
            }
        }

        return $mergedData;
    }

    private function getCustomers()
    {
        return $this->customerRepository->findAll();
    }

    private function getContracts()
    {
        return $this->contractRepository->findAll();
    }

    private function getDevices()
    {
        return $this->deviceRepository->findAll();
    }

    private function getMonthlyYields()
    {
        return $this->monthlyYieldRepository->findAll();
    }
}
