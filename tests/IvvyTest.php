<?php
declare(strict_types=1);

namespace Fcds\IvvyTest;

use Error;
use Fcds\Ivvy\Ivvy;
use Fcds\Ivvy\Job;
use Fcds\Ivvy\Signature;
use Fcds\IvvyTest\BaseTestCase;
use GuzzleHttp\Client;
use Fcds\Ivvy\Model\Company;
use Fcds\Ivvy\Model\Invoice;

/**
 * Class: IvvyTest
 *
 * @see BaseTestCase
 * @final
 * @covers Fcds\Ivvy\Ivvy
 */
final class IvvyTest extends BaseTestCase
{
    /** @var string */
    protected $apiKey;

    /** @var string */
    protected $apiSecret;

    /** @var Ivvy */
    protected $ivvy;

    public function setUp(): void
    {
        $this->apiKey = 'foo';
        $this->apiSecret = 'bar';
        $this->signatureMock = $this->createMock(Signature::class);
        $this->clientMock = $this->createMock(Client::class);

        $this->signatureMock
            ->method('sign')
            ->willReturn('baz');

        $this->ivvy = new Ivvy(
            $this->apiKey,
            $this->apiSecret,
            $this->signatureMock,
            $this->clientMock
        );
    }

    public function testPingSuccess(): void
    {
        $this->clientMock
            ->method('request')
            ->willReturn($this->generateStubResponse());

        $result = $this->ivvy->ping();

        $this->assertTrue($result);
    }

    public function testPingFailure(): void
    {
         $this->clientMock
            ->method('request')
            ->willReturn($this->generateStubResponse(404));

        $result = $this->ivvy->ping();

        $this->assertFalse($result);
    }

    public function testBatchRunSuccess(): void
    {
        $this->clientMock
            ->method('request')
            ->willReturn($this->generateStubResponse(200, json_encode(['asyncId' => 'foo'])));

        $job1 = new Job('foo', 'bar');
        $job2 = new Job('baz', 'qux');

        $result = $this->ivvy->run([$job1, $job2]);

        $this->assertEquals('foo', $result);
    }

    public function testBatchRunFailure(): void
    {
        $this->clientMock
            ->method('request')
            ->willReturn($this->generateStubResponse(400));

        $job1 = new Job('foo', 'bar');
        $job2 = new Job('baz', 'qux');

        $result = $this->ivvy->run([$job1, $job2]);

        $this->assertNull($result);
    }

    public function testBatchResultSuccess(): void
    {
        $response = [
            'results' => [
                [
                    'namespace' => 'foo',
                    'action'    => 'bar',
                    'request' => [
                    ],
                    'response' => [
                    ],
                ],
            ],
        ];

        $expectedResult = array_merge(['success' => true], $response);

        $this->clientMock
            ->method('request')
            ->willReturn($this->generateStubResponse(200, json_encode($response)));

        $result = $this->ivvy->result('foobar');

        $this->assertArraySubset($expectedResult, $result);
    }

    public function testBatchResultFailureNotCompleted(): void
    {
        $response = [
            'errorCode'    => 400,
            'specificCode' => 24114,
        ];

        $expectedResult = [
            'success' => false,
            'error'   => 'not_completed',
        ];

        $this->clientMock
            ->method('request')
            ->willReturn($this->generateStubResponse(400, json_encode($response)));

        $result = $this->ivvy->result('foobar');

        $this->assertArraySubset($expectedResult, $result);
    }

    public function testBatchResultFailure(): void
    {
        $expectedResult = [
            'success' => false,
            'error'   => 'unknown',
        ];

        $this->clientMock
            ->method('request')
            ->willReturn($this->generateStubResponse(400));

        $result = $this->ivvy->result('foobar');

        $this->assertArraySubset($expectedResult, $result);
    }

    public function testGetCompanyListSuccess(): void
    {
        $response = [
            'results' => [
                ['businessName' => 'foo'],
                ['businessName' => 'bar'],
            ],
        ];

        $expectedResult = [
            new Company(['businessName' => 'foo']),
            new Company(['businessName' => 'bar']),
        ];

        $this->clientMock
            ->method('request')
            ->willReturn($this->generateStubResponse(200, json_encode($response)));

        $companies = $this->ivvy->getCompanyList();

        $this->assertCount(2, $companies);
        $this->assertEquals($expectedResult[0]->businessName, $companies[0]->businessName);
        $this->assertEquals($expectedResult[1]->businessName, $companies[1]->businessName);
        $this->assertEquals($expectedResult, $companies);
    }

    public function testGetCompanyListFail(): void
    {
        $this->clientMock
            ->method('request')
            ->willReturn($this->generateStubResponse(400));

        $companies = $this->ivvy->getCompanyList();

        $this->assertNull($companies);
    }

    public function testGetInvoiceListSuccess()
    {
        $response = [
            'results' => [
                ['reference' => 'foo'],
                ['reference' => 'bar'],
            ],
        ];

        $expectedResult = [
            new Invoice(['reference' => 'foo']),
            new Invoice(['reference' => 'bar']),
        ];

        $this->clientMock
            ->method('request')
            ->willReturn($this->generateStubResponse(200, json_encode($response)));

        $invoices = $this->ivvy->getInvoiceList();

        $this->assertCount(2, $invoices);
        $this->assertEquals($expectedResult[0]->reference, $invoices[0]->reference);
        $this->assertEquals($expectedResult[1]->reference, $invoices[1]->reference);
        $this->assertEquals($expectedResult, $invoices);
    }

    public function testGetInvoiceListFail()
    {
        $this->clientMock
            ->method('request')
            ->willReturn($this->generateStubResponse(400));

        $invoices = $this->ivvy->getInvoiceList();

        $this->assertNull($invoices);
    }
    /**
     * Utility method to generate a stub response for the Guzzle client
     * with the passed status code and body.
     *
     * @param int $statusCode
     * @param mixed $body
     */
    private function generateStubResponse(int $statusCode = 200, $body = null)
    {
        return new class($statusCode, $body)
        {
            public function __construct(int $statusCode, $body)
            {
                $this->statusCode = $statusCode;
                $this->body = $body;
            }

            public function getStatusCode()
            {
                return $this->statusCode;
            }

            public function getBody()
            {
                return $this->body;
            }
        };
    }
}
