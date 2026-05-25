<?php

require_once __DIR__ . '/repository.php';

/**
 * Fachada orientada a objetos para futuras integraciones.
 *
 * La app publica usa funciones simples porque es un MVP en PHP puro. Esta
 * clase existe para que un controlador MVC, API REST o integracion externa
 * pueda reutilizar la misma logica sin duplicarla.
 */
class QualityPlanRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function all(): array
    {
        return get_all_plans($this->db);
    }

    public function find(int $id): ?array
    {
        return find_plan($this->db, $id);
    }

    public function findWithRelations(int $id): ?array
    {
        return get_full_plan($this->db, $id);
    }

    public function create(array $data): int
    {
        $data['id'] = '';

        return create_or_update_plan($this->db, $data);
    }

    public function update(int $id, array $data): bool
    {
        $data['id'] = (string) $id;
        create_or_update_plan($this->db, $data);

        return true;
    }

    public function updateStatus(int $id, string $status): bool
    {
        update_plan_status($this->db, $id, $status);

        return true;
    }

    public function delete(int $id): bool
    {
        soft_delete_plan($this->db, $id);

        return true;
    }
}
