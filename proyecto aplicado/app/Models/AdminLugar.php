<?php

declare(strict_types=1);

final class AdminLugar
{
    private const FIELDS = [
        'nombre', 'provincia', 'tipo', 'descripcion', 'ubicacion',
        'latitud', 'longitud', 'activo', 'imagen', 'imagen_alt',
        'foto_autor', 'foto_autor_url', 'foto_fuente', 'foto_fuente_label',
        'foto_licencia', 'foto_licencia_url', 'fuente_url',
        'destacado', 'region',
    ];

    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function all(): array
    {
        return $this->connection->query(
            'SELECT * FROM lugares
             ORDER BY COALESCE(updated_at, created_at) DESC, id DESC'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM lugares WHERE id = :id');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();
        $place = $statement->fetch(PDO::FETCH_ASSOC);

        return $place === false ? null : $place;
    }

    /** Los datos llegan validados por el controlador; solo se guardan campos del catálogo. */
    public function save(?int $id, array $validatedData): int
    {
        if ($id !== null && $id < 1) {
            throw new InvalidArgumentException('El destino seleccionado no es válido.');
        }

        $data = array_intersect_key($validatedData, array_flip(self::FIELDS));
        if ($data === []) {
            throw new InvalidArgumentException('No hay información del destino para guardar.');
        }

        // Las columnas proceden de una lista fija; los valores siempre usan parámetros.
        $fields = array_keys($data);
        if ($id === null) {
            $statement = $this->connection->prepare(
                'INSERT INTO lugares (' . implode(', ', $fields) . ', updated_at)
                 VALUES (:' . implode(', :', $fields) . ', CURRENT_TIMESTAMP)'
            );
        } else {
            $assignments = array_map(static fn (string $field): string => $field . ' = :' . $field, $fields);
            $statement = $this->connection->prepare(
                'UPDATE lugares SET ' . implode(', ', $assignments) . ', updated_at = CURRENT_TIMESTAMP
                 WHERE id = :place_id'
            );
            $statement->bindValue(':place_id', $id, PDO::PARAM_INT);
        }

        foreach ($data as $field => $value) {
            if (!is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException('La información del destino contiene un valor no válido.');
            }

            $parameterType = $value === null ? PDO::PARAM_NULL : (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            $statement->bindValue(':' . $field, $value, $parameterType);
        }
        $statement->execute();

        if ($id !== null) {
            // MySQL devuelve cero filas afectadas también cuando los valores no cambian.
            if ($statement->rowCount() === 0 && $this->find($id) === null) {
                throw new InvalidArgumentException('El destino ya no está disponible para editar.');
            }

            return $id;
        }

        return (int) $this->connection->lastInsertId();
    }
}
