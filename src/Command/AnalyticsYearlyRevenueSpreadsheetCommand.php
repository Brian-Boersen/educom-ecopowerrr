<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use App\Services\AnalyticsService;

#[AsCommand(
    name: 'analytics:yearly:revenue:spreadsheet',
    description: 'Add a short description for your command',
)]
class AnalyticsYearlyRevenueSpreadsheetCommand extends Command
{
    public function __construct
    (
        private AnalyticsService $analyticsService
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
        ->setHelp('gives a spreadsheet of total revenue of this year and a trendline based on past results')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $cus_ov = $this->analyticsService->yearlyRevenue();

        $io->success($cus_ov);

        return Command::SUCCESS;
    }
}
