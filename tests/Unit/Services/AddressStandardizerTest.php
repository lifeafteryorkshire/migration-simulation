<?php

namespace Tests\Unit;

use App\Models\Address;
use App\Services\AddressStandardizer;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AddressStandardizerTest extends TestCase
{
    protected AddressStandardizer $standardizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->standardizer = new AddressStandardizer();
    }

    public function test_standardize_returns_formatted_data_when_os_api_returns_matches(): void
    {
        Http::fake([
            '*' => Http::response([
                'results' => [
                    [
                        'DPA' => [
                            'UPRN'              => '100023336956',
                            'ADDRESS'           => '10 DOWNING STREET, LONDON, SW1A 2AA',
                            'BUILDING_NUMBER'   => '10',
                            'THOROUGHFARE_NAME' => 'DOWNING STREET',
                            'POST_TOWN'          => 'LONDON',
                            'POSTCODE'          => 'SW1A 2AA',
                            'LATITUDE'          => 51.503363,
                            'LONGITUDE'         => -0.127625,
                            'X_COORDINATE'      => 530047,
                            'Y_COORDINATE'      => 179951,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $inputData = [
            'AddressLine1' => '10 Downing Street',
            'TownCity'     => 'London',
            'Postcode'     => 'SW1A 2AA',
        ];

        $result = $this->standardizer->standardize($inputData);

        $this->assertIsArray($result);
        $this->assertEquals('10 DOWNING STREET, LONDON, SW1A 2AA', $result['AddressLine1']);
        $this->assertEquals('LONDON', $result['TownCity']);
        $this->assertEquals('SW1A 2AA', $result['Postcode']);
        $this->assertEquals('100023336956', $result['UPRN']);
        $this->assertEquals(51.503363, $result['latitude']);
        $this->assertEquals(-0.127625, $result['longitude']);
        $this->assertEquals(530047, $result['easting']);
        $this->assertEquals(179951, $result['northing']);
    }

    public function test_standardize_works_when_given_an_address_model_instance(): void
    {
        Http::fake([
            '*' => Http::response([
                'results' => [
                    [
                        'DPA' => [
                            'UPRN'      => '100023336956',
                            'ADDRESS'   => '10 DOWNING STREET',
                            'POST_TOWN' => 'LONDON',
                            'POSTCODE'  => 'SW1A 2AA',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $address = new Address(['postcode' => 'SW1A 2AA']);

        $result = $this->standardizer->standardize($address);

        $this->assertIsArray($result);
        $this->assertEquals('100023336956', $result['UPRN']);
    }

    public function test_standardize_returns_null_when_postcode_is_missing(): void
    {
        Http::fake();

        $result = $this->standardizer->standardize([]);

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_standardize_returns_null_when_api_returns_no_results(): void
    {
        Http::fake([
            '*' => Http::response(['results' => []], 200),
        ]);

        $result = $this->standardizer->standardize(['Postcode' => 'INVALID']);

        $this->assertNull($result);
    }

    public function test_standardize_returns_null_when_api_request_fails(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $result = $this->standardizer->standardize(['Postcode' => 'SW1A 2AA']);

        $this->assertNull($result);
    }
}
