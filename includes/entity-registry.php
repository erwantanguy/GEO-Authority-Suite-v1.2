<?php
/**
 * GEO Authority Suite - Entity Registry
 */

if (!defined('ABSPATH')) {
    exit;
}

global $geo_entities_registry;
$geo_entities_registry = [];

function geo_register_entity(array $entity): void {
    global $geo_entities_registry;

    if (empty($entity['@id'])) {
        return;
    }

    if (isset($entity['@context'])) {
        unset($entity['@context']);
    }

    if (isset($entity['@type']) && in_array($entity['@type'], ['worksFor', 'memberOf'])) {
        return;
    }

    $geo_entities_registry[$entity['@id']] = $entity;
}

function geo_get_entities(): array {
    global $geo_entities_registry;
    return $geo_entities_registry ?? [];
}

function geo_get_entity(string $id): ?array {
    global $geo_entities_registry;
    return $geo_entities_registry[$id] ?? null;
}

function geo_count_entities(): int {
    global $geo_entities_registry;
    return count($geo_entities_registry ?? []);
}

function geo_reset_entities(): void {
    global $geo_entities_registry;
    $geo_entities_registry = [];
}

function geo_get_entities_by_type(string $type): array {
    $entities = geo_get_entities();
    return array_filter($entities, function ($entity) use ($type) {
        return isset($entity['@type']) && $entity['@type'] === $type;
    });
}
