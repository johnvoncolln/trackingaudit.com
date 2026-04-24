<?php

namespace Tests\Feature;

use App\Jobs\DelegateTrackersJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CsvUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Queue::fake();
    }

    public function test_csv_upload_requires_authentication(): void
    {
        $response = $this->post(route('tracking.import'), [
            'csv_file' => UploadedFile::fake()->create('test.csv', 100),
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_csv_file_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tracking.import'), []);

        $response->assertSessionHasErrors(['csv_file']);
    }

    public function test_only_csv_files_are_allowed(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.txt', 100);

        $response = $this->actingAs($user)->post(route('tracking.import'), [
            'csv_file' => $file,
        ]);

        $response->assertSessionHasErrors(['csv_file']);
    }

    public function test_csv_file_size_limit(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.csv', 3000); // 3MB

        $response = $this->actingAs($user)->post(route('tracking.import'), [
            'csv_file' => $file,
        ]);

        $response->assertSessionHasErrors(['csv_file']);
    }

    public function test_successful_csv_upload_dispatches_job(): void
    {
        $user = User::factory()->create();

        $csvContent = "tracking_number,carrier,reference_id,reference_name\n";
        $csvContent .= "1Z999AA10123456784,UPS,REF001,Test Package 1\n";
        $csvContent .= "1Z999AA10123456785,UPS,REF002,Test Package 2\n";

        $file = UploadedFile::fake()->createWithContent('valid.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('tracking.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('tracking.index'));
        $response->assertSessionHas('flash.banner', 'Successfully imported 2 tracking numbers.');
        $response->assertSessionHas('flash.bannerStyle', 'success');

        Queue::assertPushed(DelegateTrackersJob::class);
    }

    public function test_mixed_carrier_csv_upload_succeeds(): void
    {
        $user = User::factory()->create();

        $csvContent = "tracking_number,carrier,reference_id,reference_name\n";
        $csvContent .= "1Z999AA10123456784,UPS,REF001,UPS Package\n";
        $csvContent .= "9400111899223377665544,USPS,REF002,USPS Package\n";
        $csvContent .= "123456789012,FedEx,REF003,FedEx Package\n";

        $file = UploadedFile::fake()->createWithContent('mixed.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('tracking.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('tracking.index'));
        $response->assertSessionHas('flash.banner', 'Successfully imported 3 tracking numbers.');

        Queue::assertPushed(DelegateTrackersJob::class);
    }

    public function test_csv_without_carrier_column_auto_detects(): void
    {
        $user = User::factory()->create();

        $csvContent = "tracking_number,reference_id\n";
        $csvContent .= "1Z999AA10123456784,REF001\n";
        $csvContent .= "123456789012,REF002\n";

        $file = UploadedFile::fake()->createWithContent('no_carrier.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('tracking.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('tracking.index'));
        $response->assertSessionHas('flash.banner', 'Successfully imported 2 tracking numbers.');

        Queue::assertPushed(DelegateTrackersJob::class);
    }

    public function test_csv_with_invalid_carrier_aborts_import(): void
    {
        $user = User::factory()->create();

        $csvContent = "tracking_number,carrier,reference_id\n";
        $csvContent .= "1Z999AA10123456784,INVALID_CARRIER,REF001\n";

        $file = UploadedFile::fake()->createWithContent('invalid_carrier.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('tracking.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect(route('tracking.index'));
        $response->assertSessionHas('flash.bannerStyle', 'danger');

        Queue::assertNotPushed(DelegateTrackersJob::class);
    }

    public function test_csv_with_too_many_records(): void
    {
        $user = User::factory()->create();

        $csvContent = "tracking_number,carrier,reference_id,reference_name\n";
        for ($i = 0; $i < 1501; $i++) {
            $csvContent .= "1Z999AA1012345678{$i},UPS,REF{$i},Test Package {$i}\n";
        }

        $file = UploadedFile::fake()->createWithContent('too_many.csv', $csvContent);

        $response = $this->actingAs($user)->post(route('tracking.import'), [
            'csv_file' => $file,
        ]);

        $response->assertSessionHasErrors(['csv_file' => 'CSV file contains more than 1500 records.']);
    }
}
