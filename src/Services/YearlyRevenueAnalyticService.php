<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

class YearlyRevenueAnalyticService extends AnalyticsService
{
    public function yearlyRevenue()
    {
        $monthlyYields = $this->getMonthlyYields();

        $sortedData = $this->sortCustomerData($monthlyYields, 1);

        //calculating yearly revenue per customer
        $revenues = [];

        foreach($sortedData as $customerData)
        {
            $revenues[] = $this->calcYearlyCustomerRevenue($customerData,true);
        }

        //combine calculated revenue
        $montlyRevenue = $this->combineMonthlyRevenue($revenues);        
        $revenue = $this->combineYearlyRevenues($revenues);
        
        //making new spreadsheet
        $fileName = './public/Spreadsheets/yearlyRevenueOverview.xlsx';
        $spreadsheet = new Spreadsheet($fileName);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('yearly revenue Overview');

        $col = 'A';
        $row = 1;

        //setheaders
        $startDate = $this->findEurliestDate($sortedData);

        $dateList = $this->getDateList($startDate, 1);
        
        //put data in sheet
        $this->setHeaders($sheet, $col, $row, ['Total revenue',$dateList]);

        $row++;
        
        $col = $this->fillRow($sheet, $col, $row, $revenue,'€','',3);

        $col = $this->fillRow($sheet, $col, $row, $montlyRevenue,'€','',2);

        $this->saveSheet($spreadsheet,$sheet, $fileName);

        return 'yearly revenue overview spreadsheet created. total revenue is: €'. round($revenue,2) .' . check ' . $fileName . ' for more results';
    }
}