<?php

namespace Gov2lib\Exceptions;

/**
 * Operasi SQL-level yang tidak bisa dipetakan ke backend non-SQL.
 *
 * Dilempar SupabaseAdapter untuk method DatabaseInterface yang tidak punya
 * padanan PostgREST (raw query, transaksi, WHERE non-equality). Sesuai
 * keputusan T0 #6085: kontrak lintas-tier adalah CrudRepositoryInterface;
 * DatabaseInterface SQL-level penuh hanya dijamin MeekroAdapter.
 */
class UnsupportedDriverOperationException extends DatabaseException
{
}
