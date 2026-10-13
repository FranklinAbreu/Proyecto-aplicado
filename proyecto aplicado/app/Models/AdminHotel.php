<?php

declare(strict_types=1);

final class AdminHotel
{
    private const FIELDS = [
        'nombre', 'provincia', 'municipio', 'descripcion', 'ubicacion',
        'latitud', 'longitud', 'telefono', 'sitio_web', 'reserva_url', 'servicios',
        'imagen', 'imagen_alt', 'foto_autor', 'foto_autor_url', 'foto_fuente',
        'foto_fuente_label', 'foto_licencia', 'foto_licencia_url', 'fuente_url',
        'verificado_en', 'activo',
    ];

    public function __construct(private PDO $connection)
    {
    }

    public function all(): array
    {
        $hotels = $this->connection->query(
            'SELECT * FROM hoteles ORDER BY COALESCE(updated_at, created_at) DESC, id DESC'
        )->fetchAll(PDO::FETCH_ASSOC);
        $links = $this->connection->query('SELECT hotel_id, destino_id FROM hotel_lugar ORDER BY hotel_id, destino_id')
            ->fetchAll(PDO::FETCH_ASSOC);
        $byHotel = [];
        foreach ($links as $link) {
            $byHotel[(int) $link['hotel_id']][] = (int) $link['destino_id'];
        }
        foreach ($hotels as &$hotel) {
            $hotel['destino_ids'] = $byHotel[(int) $hotel['id']] ?? [];
        }
        unset($hotel);
        return $hotels;
    }

    public function find(int $id): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM hoteles WHERE id = :id');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();
        $hotel = $statement->fetch(PDO::FETCH_ASSOC);
        if ($hotel === false) {
            return null;
        }
        $statement = $this->connection->prepare('SELECT destino_id FROM hotel_lugar WHERE hotel_id = :id ORDER BY destino_id');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();
        $hotel['destino_ids'] = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
        return $hotel;
    }

    /** Guarda la ficha y sustituye sus asociaciones de forma atómica. */
    public function save(?int $id, array $validatedData, array $destinoIds): int
    {
        if ($id !== null && $id < 1) {
            throw new InvalidArgumentException('El hotel seleccionado no es válido.');
        }
        $data = array_intersect_key($validatedData, array_flip(self::FIELDS));
        if ($data === []) {
            throw new InvalidArgumentException('No hay información del hotel para guardar.');
        }
        foreach ($data as $value) {
            if (!is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException('La información del hotel contiene un valor no válido.');
            }
        }
        $ids = self::destinationIds($destinoIds);
        $ownsTransaction = !$this->connection->inTransaction();
        $savepoint = 'hotel_save_' . bin2hex(random_bytes(8));
        if ($ownsTransaction) {
            $this->connection->beginTransaction();
        } else {
            $this->connection->exec('SAVEPOINT ' . $savepoint);
        }
        try {
            if ($id !== null && $this->find($id) === null) {
                throw new InvalidArgumentException('El hotel ya no está disponible para editar.');
            }
            if ($ids !== []) {
                $statement = $this->connection->prepare(
                    'SELECT id FROM lugares WHERE id IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')'
                );
                foreach ($ids as $position => $destinationId) {
                    $statement->bindValue($position + 1, $destinationId, PDO::PARAM_INT);
                }
                $statement->execute();
                if (count($statement->fetchAll(PDO::FETCH_COLUMN)) !== count($ids)) {
                    throw new InvalidArgumentException('Uno de los destinos seleccionados ya no está disponible. Revisa las asociaciones.');
                }
            }

            $fields = array_keys($data);
            if ($id === null) {
                $statement = $this->connection->prepare(
                    'INSERT INTO hoteles (' . implode(', ', $fields) . ', updated_at)
                     VALUES (:' . implode(', :', $fields) . ', CURRENT_TIMESTAMP)'
                );
            } else {
                $assignments = array_map(static fn (string $field): string => $field . ' = :' . $field, $fields);
                $statement = $this->connection->prepare(
                    'UPDATE hoteles SET ' . implode(', ', $assignments) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :hotel_id'
                );
                $statement->bindValue(':hotel_id', $id, PDO::PARAM_INT);
            }
            foreach ($data as $field => $value) {
                $type = $value === null ? PDO::PARAM_NULL : (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
                $statement->bindValue(':' . $field, $value, $type);
            }
            $statement->execute();
            if ($id === null) {
                $id = (int) $this->connection->lastInsertId();
            }
            $statement = $this->connection->prepare('DELETE FROM hotel_lugar WHERE hotel_id = :hotel_id');
            $statement->bindValue(':hotel_id', $id, PDO::PARAM_INT);
            $statement->execute();
            if ($ids !== []) {
                $statement = $this->connection->prepare('INSERT INTO hotel_lugar (hotel_id, destino_id) VALUES (:hotel_id, :destino_id)');
                foreach ($ids as $destinationId) {
                    $statement->bindValue(':hotel_id', $id, PDO::PARAM_INT);
                    $statement->bindValue(':destino_id', $destinationId, PDO::PARAM_INT);
                    $statement->execute();
                }
            }
            if ($ownsTransaction) {
                $this->connection->commit();
            } else {
                $this->connection->exec('RELEASE SAVEPOINT ' . $savepoint);
            }
            return $id;
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) {
                if ($ownsTransaction) {
                    $this->connection->rollBack();
                } else {
                    $this->connection->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
                    $this->connection->exec('RELEASE SAVEPOINT ' . $savepoint);
                }
            }
            throw $exception;
        }
    }

    private static function destinationIds(array $values): array
    {
        $ids = [];
        foreach ($values as $value) {
            if ((!is_int($value) && !is_string($value)) || !preg_match('/\A[1-9][0-9]{0,9}\z/D', (string) $value)) {
                throw new InvalidArgumentException('Selecciona destinos válidos para asociar al hotel.');
            }
            $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4294967295]]);
            if ($id === false) {
                throw new InvalidArgumentException('Selecciona destinos válidos para asociar al hotel.');
            }
            $ids[$id] = $id;
        }
        return array_values($ids);
    }
}
