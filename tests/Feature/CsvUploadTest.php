<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CsvUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_csv_upload_requires_authentication()
    {
        $response = $this->post(route('tracking.import'), [
            'csv_file' => UploadedFile::fake()->create('test.csv', 100)
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_csv_file_is_required()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tracking.import'), []);

        $response->assertSessionHasErrors(['csv_file']);
    }

    public function test_only_csv_files_are_allowed()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.txt', 100);

        $response = $this->actingAs($user)->post(route('tracking.import'), [
            'csv_file' => $file
        ]);

        $response->assertSessionHasErrors(['csv_file']);
    }

    public function test_csv_file_size_limit()
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('test.csv', 3000); // 3MB

        $response = $this->actingAs($user)->post(route('tracking.import'), [
            'csv_file' => $file
        ]);

        $response->assertSessionHasErrors(['csv_file']);
    }

    public function test_successful_csv_upload()
    {
        $user = User::factory()->create();
        
        // Create a valid CSV content
        $csvContent = "tracking_number,reference_id,reference_name\n";
        $csvContent .= "1Z999AA1234567890,REF001,Test Package 1\n";
        $csvContent .= "1Z999AA1234567891,REF002,Test Package 2\n";

        $file = UploadedFile::fake()->createWithContent(
            'valid.csv',
            $csvContent
        );

        $response = $this->actingAs($user)->post(route('tracking.import'), [
            'csv_file' => $file
        ]);

        $response->assertRedirect(route('tracking.index'));
        $response->assertSessionHas('flash.banner', 'Successfully imported 2 tracking numbers.');
        $response->assertSessionHas('flash.bannerStyle', 'success');

        $this->assertDatabaseHas('trackers', [
            'user_id' => $user->id,
            'tracking_number' => '1Z999AA1234567890',
            'reference_id' => 'REF001',
            'reference_name' => 'Test Package 1'
        ]);

        $this->assertDatabaseHas('trackers', [
            'user_id' => $user->id,
            'tracking_number' => '1Z999AA1234567891',
            'reference_id' => 'REF002',
            'reference_name' => 'Test Package 2'
        ]);
    }

    public function test_csv_with_invalid_tracking_numbers()
    {
        $user = User::factory()->create();
        
        // Create CSV with invalid tracking numbers
        $csvContent = "tracking_number,reference_id,reference_name\n";
        $csvContent .= "INVALID123,REF001,Test Package 1\n";
        $csvContent .= "1Z999AA1234567890,REF002,Test Package 2\n";

        $file = UploadedFile::fake()->createWithContent(
            'invalid.csv',
            $csvContent
        );

        $response = $this->actingAs($user)->post(route('tracking.import'), [
            'csv_file' => $file
        ]);

        $response->assertRedirect(route('tracking.index'));
        $response->assertSessionHas('flash.banner', 'Successfully imported 1 tracking numbers. 1 records failed validation.');
        $response->assertSessionHas('flash.bannerStyle', 'warning');

        $this->assertDatabaseMissing('trackers', [
            'tracking_number' => 'INVALID123'
        ]);

        $this->assertDatabaseHas('trackers', [
            'tracking_number' => '1Z999AA1234567890'
        ]);
    }

    public function test_csv_with_too_many_records()
    {
        $user = User::factory()->create();
        
        // Create CSV with 501 records
        $csvContent = "tracking_number,reference_id,reference_name\n";
        for ($i = 0; $i < 501; $i++) {
            $csvContent .= "1Z999AA123456789{$i},REF{$i},Test Package {$i}\n";
        }

        $file = UploadedFile::fake()->createWithContent(
            'too_many.csv',
            $csvContent
        );

        $response = $this->actingAs($user)->post(route('tracking.import'), [
            'csv_file' => $file
        ]);

        $response->assertSessionHasErrors(['csv_file' => 'CSV file contains more than 500 records.']);
    }
}
