<?php
/** @var array $block */
/** @var array $data */

$blockTitle = trim((string)($data['title'] ?? $block['title'] ?? ''));
$columns = [];
$rawColumns = $data['columns'] ?? null;
if (is_array($rawColumns)) {
    foreach (array_slice($rawColumns, 0, 12) as $column) {
        if (!is_array($column)) continue;
        $columns[] = [
            'blocks' => is_array($column['blocks'] ?? null) ? $column['blocks'] : [],
        ];
    }
} else {
    // Rueckwaertskompatibel fuer bereits gespeicherte 3-Spalten-Bloecke.
    $columns = [
        ['blocks' => is_array($data['left_blocks'] ?? null) ? $data['left_blocks'] : []],
        ['blocks' => is_array($data['center_blocks'] ?? null) ? $data['center_blocks'] : []],
        ['blocks' => is_array($data['right_blocks'] ?? null) ? $data['right_blocks'] : []],
    ];
}

$columns = array_values(array_filter(
    $columns,
    static fn(array $column): bool => ($column['blocks'] ?? []) !== []
));
$hasAny = $columns !== [];

if ($blockTitle === '' && !$hasAny) {
    return;
}
?>
<section class="block block-three-columns block-multi-columns">
  <div class="block-three-columns__inner block-multi-columns__inner">
    <?php if ($blockTitle !== ''): ?>
      <h2 class="block-three-columns__title"><?= htmlspecialchars($blockTitle, ENT_QUOTES, 'UTF-8') ?></h2>
    <?php endif; ?>
    <?php if ($columns !== []): ?>
      <div class="block-three-columns__grid block-multi-columns__grid">
        <?php foreach ($columns as $column): ?>
          <div class="block-three-columns__column block-multi-columns__column">
            <?php render_page_blocks($column['blocks'], compact('contactFormStates', 'currentSlug', 'contactTurnstileSiteKey', 'publicSettings', 'client')); ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
