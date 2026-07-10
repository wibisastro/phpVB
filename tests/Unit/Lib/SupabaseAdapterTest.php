<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\Database\SupabaseAdapter;
use Gov2lib\Database\SupabaseCrudRepository;
use Gov2lib\Exceptions\SupabaseException;
use Gov2lib\Exceptions\UnsupportedDriverOperationException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * Unit tests SupabaseAdapter + SupabaseCrudRepository (fase T3 #6085).
 *
 * Guzzle MockHandler — tanpa jaringan. Menguji pemetaan operasi →
 * request PostgREST (method, path, query param, header), error mapping
 * 4xx/5xx → SupabaseException, dan typed exception untuk operasi
 * SQL-level yang tak terpetakan.
 */
class SupabaseAdapterTest extends TestCase
{
    private MockHandler $mock;

    /** @var array<int, array{request: RequestInterface}> */
    private array $history = [];

    private function adapter(array $config = []): SupabaseAdapter
    {
        $this->mock = new MockHandler();
        $this->history = [];

        $stack = HandlerStack::create($this->mock);
        $stack->push(Middleware::history($this->history));

        return new SupabaseAdapter(
            $config + ['url' => 'https://gajah.test', 'key' => 'anon-key'],
            new Client(['handler' => $stack])
        );
    }

    private function lastRequest(): RequestInterface
    {
        return end($this->history)['request'];
    }

    // -- konstruksi -----------------------------------------------------

    public function testKredensialKosongDitolak(): void
    {
        $this->expectException(SupabaseException::class);
        $this->expectExceptionMessageMatches('/SupabaseCredentialMissing/');

        new SupabaseAdapter(['url' => 'https://gajah.test', 'key' => '']);
    }

    // -- select / browse ------------------------------------------------

    public function testSelectMemetakanFilterLimitOffsetOrder(): void
    {
        $db = $this->adapter();
        $this->mock->append(new Response(200, [], '[{"id":1,"nama":"Andi"}]'));

        $rows = $db->restSelect(
            'pilot',
            ['status' => 'on', 'parent_id' => 3],
            limit: 25,
            offset: 50,
            orderBy: 'id DESC, nama'
        );

        $request = $this->lastRequest();
        parse_str($request->getUri()->getQuery(), $query);

        $this->assertEquals('Andi', $rows[0]['nama']);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/rest/v1/pilot', $request->getUri()->getPath());
        $this->assertEquals(
            [
                'status' => 'eq.on',
                'parent_id' => 'eq.3',
                'order' => 'id.desc,nama.asc',
                'limit' => '25',
                'offset' => '50',
            ],
            $query
        );
        $this->assertEquals('anon-key', $request->getHeaderLine('apikey'));
        $this->assertEquals('Bearer anon-key', $request->getHeaderLine('Authorization'));
    }

    public function testFilterNullDanBoolPakaiIs(): void
    {
        $db = $this->adapter();
        $this->mock->append(new Response(200, [], '[]'));

        $db->restSelect('pilot', ['deleted_at' => null, 'aktif' => true]);

        parse_str($this->lastRequest()->getUri()->getQuery(), $query);
        $this->assertEquals(['deleted_at' => 'is.null', 'aktif' => 'is.true'], $query);
    }

    // -- insert -----------------------------------------------------------

    public function testInsertPostDenganReturnRepresentation(): void
    {
        $db = $this->adapter();
        $this->mock->append(new Response(201, [], '[{"id":42,"nama":"Andi"}]'));

        $id = $db->insert('pilot', ['nama' => 'Andi']);
        $request = $this->lastRequest();

        $this->assertSame(42, $id);
        $this->assertSame(42, $db->insertId());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('return=representation', $request->getHeaderLine('Prefer'));
        $this->assertEquals(['nama' => 'Andi'], json_decode((string) $request->getBody(), true));
    }

    // -- update / delete via fragmen WHERE MeekroDB ----------------------

    public function testUpdateMenerjemahkanWhereEqualitySederhana(): void
    {
        $db = $this->adapter();
        $this->mock->append(new Response(200, [], '[{"id":7}]'));

        $affected = $db->update('pilot', ['nama' => 'Budi'], 'id=%i', 7);
        $request = $this->lastRequest();
        parse_str($request->getUri()->getQuery(), $query);

        $this->assertSame(1, $affected);
        $this->assertSame(1, $db->affectedRows());
        $this->assertEquals('PATCH', $request->getMethod());
        $this->assertEquals(['id' => 'eq.7'], $query);
    }

    public function testDeleteWhereMajemukAnd(): void
    {
        $db = $this->adapter();
        $this->mock->append(new Response(200, [], '[{"id":1},{"id":2}]'));

        $affected = $db->delete('pilot', 'parent_id=%i AND status=%s', 3, 'off');
        parse_str($this->lastRequest()->getUri()->getQuery(), $query);

        $this->assertSame(2, $affected);
        $this->assertEquals('DELETE', $this->lastRequest()->getMethod());
        $this->assertEquals(['parent_id' => 'eq.3', 'status' => 'eq.off'], $query);
    }

    public function testWhereNonEqualityDitolakTyped(): void
    {
        $db = $this->adapter();

        $this->expectException(UnsupportedDriverOperationException::class);

        $db->delete('pilot', 'id>%i', 5);
    }

    public function testWriteTanpaFilterDitolak(): void
    {
        $db = $this->adapter();

        $this->expectException(SupabaseException::class);
        $this->expectExceptionMessageMatches('/SupabaseUnfilteredWrite/');

        $db->restUpdate('pilot', ['status' => 'off'], []);
    }

    // -- count ------------------------------------------------------------

    public function testCountPakaiHeadPreferCountExact(): void
    {
        $db = $this->adapter();
        $this->mock->append(new Response(200, ['Content-Range' => '*/57'], ''));

        $total = $db->count('pilot');
        $request = $this->lastRequest();

        $this->assertSame(57, $total);
        $this->assertEquals('HEAD', $request->getMethod());
        $this->assertEquals('count=exact', $request->getHeaderLine('Prefer'));
    }

    public function testCountDenganWhereTerjemah(): void
    {
        $db = $this->adapter();
        $this->mock->append(new Response(206, ['Content-Range' => '0-9/12'], ''));

        $total = $db->count('pilot', 'parent_id=%i', 3);
        parse_str($this->lastRequest()->getUri()->getQuery(), $query);

        $this->assertSame(12, $total);
        $this->assertEquals(['parent_id' => 'eq.3'], $query);
    }

    // -- operasi tak terpetakan -------------------------------------------

    public function testRawQueryDanTransaksiDitolakTyped(): void
    {
        $db = $this->adapter();

        foreach (
            [
                fn () => $db->query('SELECT * FROM pilot'),
                fn () => $db->queryFirstRow('SELECT * FROM pilot WHERE id=%i', 1),
                fn () => $db->queryFirstColumn('SELECT id FROM pilot'),
                fn () => $db->queryFirstField('SELECT COUNT(*) FROM pilot'),
                fn () => $db->startTransaction(),
                fn () => $db->commit(),
                fn () => $db->rollback(),
            ] as $call
        ) {
            try {
                $call();
                $this->fail('Operasi SQL-level seharusnya melempar UnsupportedDriverOperationException');
            } catch (UnsupportedDriverOperationException $e) {
                $this->assertStringContainsString('UnsupportedDriverOperation', $e->getMessage());
            }
        }
    }

    // -- error mapping ------------------------------------------------------

    public function testErrorPostgrestDipetakanKeSupabaseException(): void
    {
        $db = $this->adapter();
        $this->mock->append(new Response(
            404,
            [],
            '{"code":"42P01","message":"relation \"public.tak_ada\" does not exist","hint":null,"details":null}'
        ));

        try {
            $db->restSelect('tak_ada');
            $this->fail('4xx seharusnya melempar SupabaseException');
        } catch (SupabaseException $e) {
            $this->assertSame(404, $e->httpStatus);
            $this->assertSame('42P01', $e->pgCode);
            $this->assertStringContainsString('SupabaseError: HTTP 404 [42P01]', $e->getMessage());
        }
    }

    public function testRlsMenolakWriteJadiSupabaseException(): void
    {
        $db = $this->adapter();
        $this->mock->append(new Response(
            401,
            [],
            '{"code":"42501","message":"new row violates row-level security policy","hint":null}'
        ));

        try {
            $db->insert('pilot', ['nama' => 'X']);
            $this->fail('RLS violation seharusnya melempar SupabaseException');
        } catch (SupabaseException $e) {
            $this->assertSame('42501', $e->pgCode);
        }
    }

    // -- columnList via OpenAPI ---------------------------------------------

    public function testColumnListDariOpenApiDanDicache(): void
    {
        $db = $this->adapter();
        $this->mock->append(new Response(200, [], json_encode([
            'definitions' => [
                'pilot' => ['properties' => [
                    'id' => ['type' => 'integer', 'format' => 'bigint'],
                    'nama' => ['type' => 'string', 'format' => 'text'],
                ]],
            ],
        ])));

        $columns = $db->columnList('pilot');

        $this->assertEquals(['id', 'nama'], array_keys($columns));
        $this->assertEquals('bigint', $columns['id']['type']);

        // Panggilan kedua tidak menambah request (cache definitions)
        $db->columnList('pilot');
        $this->assertCount(1, $this->history);

        $this->expectException(SupabaseException::class);
        $this->expectExceptionMessageMatches('/SupabaseUnknownTable/');
        $db->columnList('tak_ada');
    }

    // -- schema non-default ---------------------------------------------------

    public function testSchemaNonPublicPakaiHeaderProfile(): void
    {
        $db = $this->adapter(['schema' => 'sakip']);
        $this->mock->append(new Response(200, [], '[]'));
        $this->mock->append(new Response(201, [], '[{"id":1}]'));

        $db->restSelect('pilot');
        $this->assertEquals('sakip', $this->lastRequest()->getHeaderLine('Accept-Profile'));

        $db->insert('pilot', ['nama' => 'X']);
        $this->assertEquals('sakip', $this->lastRequest()->getHeaderLine('Content-Profile'));
    }

    // -- SupabaseCrudRepository ------------------------------------------------

    public function testRepositoryCrudMemetakanKeAdapter(): void
    {
        $db = $this->adapter();
        $repo = new SupabaseCrudRepository($db);

        $this->mock->append(new Response(201, [], '[{"id":9,"nama":"Andi"}]'));
        $this->assertSame(9, $repo->add('pilot', ['nama' => 'Andi']));

        $this->mock->append(new Response(200, [], '[{"id":9,"nama":"Andi"}]'));
        $row = $repo->read('pilot', 9);
        $this->assertEquals('Andi', $row['nama']);
        parse_str($this->lastRequest()->getUri()->getQuery(), $query);
        $this->assertEquals(['id' => 'eq.9', 'limit' => '1', 'offset' => '0'], $query);

        $this->mock->append(new Response(200, [], '[{"id":9}]'));
        $this->assertSame(1, $repo->update('pilot', ['nama' => 'Budi'], 9));

        $this->mock->append(new Response(200, [], '[{"id":9}]'));
        $this->assertSame(1, $repo->delete('pilot', 9));

        $this->mock->append(new Response(200, ['Content-Range' => '*/3'], ''));
        $this->assertSame(3, $repo->count('pilot', ['status' => 'on']));
    }

    public function testRepositoryReadKosongNull(): void
    {
        $db = $this->adapter();
        $repo = new SupabaseCrudRepository($db);

        $this->mock->append(new Response(200, [], '[]'));

        $this->assertNull($repo->read('pilot', 404));
    }
}
