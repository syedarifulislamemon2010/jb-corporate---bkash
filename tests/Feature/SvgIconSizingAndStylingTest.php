<?php

namespace Tests\Feature;

use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SvgIconSizingAndStylingTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_modal_renders_all_svgs_with_explicit_dimensions(): void
    {
        $batch = BkashTransactionBatch::create([
            'file_name'        => 'TEST_BATCH_SVG.xlsx',
            'total_data'       => 2,
            'total_amount'     => 10000.00,
            'transaction_type' => 'RTGS',
            'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        $rendered = view('filament.resources.bkash-batches.detail-modal', ['batch' => $batch])->render();

        // Ensure no raw unstyled svg without width/height or style
        $this->assertStringContainsString('style="width: 15px; height: 15px; min-width: 15px;', $rendered);
        $this->assertStringContainsString('style="width: 18px; height: 18px; min-width: 18px;', $rendered);
        $this->assertStringContainsString('Download as Excel', $rendered);
        $this->assertStringContainsString('Download as CSV', $rendered);
    }

    public function test_file_group_header_renders_download_svg_with_explicit_dimensions(): void
    {
        $rendered = view('filament.resources.bkash-transactions.file-group-header', [
            'index'           => 1,
            'fileName'        => 'TEST_HEADER_SVG.xlsx',
            'channel'         => 'A2A',
            'totalTrn'        => 5,
            'successTrn'      => 4,
            'failedTrn'       => 1,
            'formattedAmount' => '50,000.00',
            'downloadUrl'     => 'http://localhost/download',
        ])->render();

        $this->assertStringContainsString('style="width: 14px; height: 14px; min-width: 14px;"', $rendered);
        $this->assertStringContainsString('Download', $rendered);
    }

    public function test_custom_styles_contains_svg_sizing_fallbacks(): void
    {
        $rendered = view('filament.custom-styles')->render();

        $this->assertStringContainsString('svg.w-3\.5', $rendered);
        $this->assertStringContainsString('svg.w-4', $rendered);
        $this->assertStringContainsString('svg.w-6', $rendered);
    }
}
