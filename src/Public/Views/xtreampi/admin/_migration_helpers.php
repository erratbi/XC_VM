<?php
/** Shared, presentation-only helpers for the XtreamPi migration packets. */
if (!function_exists('sc_m_escape')) {
    function sc_m_escape($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('sc_m_value')) {
    function sc_m_value(array $row, string $key, string $default = ''): string
    {
        return sc_m_escape(array_key_exists($key, $row) ? $row[$key] : $default);
    }
}
if (!function_exists('sc_m_list')) {
    function sc_m_list(string $title, string $heading, string $back, string $add, array $rows, array $columns, string $idKey = 'id', string $deleteParam = 'id'): void
    {
        ?>
        <section class="sc-migration-list">
            <div class="sc-page-heading"><div><p class="sc-eyebrow">Administration</p><h1><?php echo sc_m_escape($title); ?></h1></div><div class="sc-page-actions"><?php if ($add !== ''): ?><a class="sc-button sc-button-primary" href="<?php echo sc_m_escape($add); ?>"><i class="fe-plus" aria-hidden="true"></i> Add</a><?php endif; ?></div></div>
            <div class="sc-toolbar"><label class="sc-search-field"><i class="fe-search" aria-hidden="true"></i><span class="sc-visually-hidden">Search</span><input type="search" data-sc-migration-search placeholder="Search <?php echo sc_m_escape(strtolower($title)); ?>"></label></div>
            <div class="sc-data-panel"><div class="sc-table-scroll"><table class="sc-data-table" data-sc-migration-table><thead><tr><?php foreach ($columns as $column => $label): ?><th><?php echo sc_m_escape($label); ?></th><?php endforeach; ?><th>Actions</th></tr></thead><tbody><?php if (!$rows): ?><tr><td class="sc-table-state" colspan="<?php echo count($columns) + 1; ?>">No <?php echo sc_m_escape(strtolower($title)); ?> found.</td></tr><?php else: foreach ($rows as $row): $id = $row[$idKey] ?? ($row['profile_id'] ?? ''); ?><tr data-sc-row><?php foreach ($columns as $column => $label): ?><td data-sc-cell><?php echo sc_m_escape(is_array($row[$column] ?? null) ? implode(', ', $row[$column]) : ($row[$column] ?? '—')); ?></td><?php endforeach; ?><td><a class="sc-row-action" href="<?php echo sc_m_escape($back . '?id=' . rawurlencode((string) $id)); ?>">Edit</a><?php if ($heading === 'provider' && $id !== ''): ?><button type="button" class="sc-row-action" data-sc-reload data-reload-url="<?php echo sc_m_escape('api?action=provider&sub=reload&id=' . rawurlencode((string) $id)); ?>">Reload</button><?php endif; ?><?php if ($id !== ''): ?><button type="button" class="sc-row-action" data-sc-delete data-delete-url="<?php echo sc_m_escape('api?action=' . $heading . '&sub=delete&' . $deleteParam . '=' . rawurlencode((string) $id)); ?>">Delete</button><?php endif; ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div>
        </section>
        <?php
    }
}
if (!function_exists('sc_m_editor')) {
    function sc_m_editor(string $title, string $action, string $back, ?array $row, array $fields, string $submitName, string $submitValue = 'Save'): void
    {
        $editing = is_array($row) && !empty($row['id'] ?? ($row['profile_id'] ?? null));
        ?>
        <section class="sc-migration-editor"><div class="sc-page-heading"><div><p class="sc-eyebrow">Administration</p><h1><?php echo sc_m_escape($title); ?></h1></div><div class="sc-page-actions"><a class="sc-button sc-button-secondary" href="<?php echo sc_m_escape($back); ?>">Back</a></div></div><form class="sc-form" action="<?php echo sc_m_escape($action); ?>" method="post">
            <?php if ($editing): ?><input type="hidden" name="edit" value="<?php echo sc_m_escape($row['id'] ?? ($row['profile_id'] ?? '')); ?>"><?php endif; ?>
            <section class="sc-form-section"><div class="sc-form-grid"><?php foreach ($fields as $field): $name = $field['name']; $type = $field['type'] ?? 'text'; $value = $row[$name] ?? ($field['default'] ?? ''); ?><label><?php echo sc_m_escape($field['label']); ?><?php if ($type === 'textarea'): ?><textarea name="<?php echo sc_m_escape($name); ?>" rows="4"><?php echo sc_m_escape($value); ?></textarea><?php elseif ($type === 'select'): ?><select name="<?php echo sc_m_escape($name); ?>"><?php foreach (($field['options'] ?? []) as $optionValue => $optionLabel): ?><option value="<?php echo sc_m_escape($optionValue); ?>"<?php echo (string) $optionValue === (string) $value ? ' selected' : ''; ?>><?php echo sc_m_escape($optionLabel); ?></option><?php endforeach; ?></select><?php elseif ($type === 'checkbox'): ?><input type="checkbox" name="<?php echo sc_m_escape($name); ?>" value="1"<?php echo !empty($value) ? ' checked' : ''; ?>><?php else: ?><input type="<?php echo sc_m_escape($type); ?>" name="<?php echo sc_m_escape($name); ?>" value="<?php echo sc_m_escape($value); ?>"<?php echo !empty($field['required']) ? ' required' : ''; ?>><?php endif; ?></label><?php endforeach; ?></div></section><div class="sc-form-error" data-sc-form-error hidden></div><div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit" name="<?php echo sc_m_escape($submitName); ?>" value="<?php echo sc_m_escape($submitValue); ?>">Save</button><a class="sc-button sc-button-secondary" href="<?php echo sc_m_escape($back); ?>">Cancel</a></div></form></section>
        <?php
    }
}
if (!function_exists('sc_m_bulk')) {
    function sc_m_bulk(string $title, string $action, string $selectedName, array $fields, string $selectionLabel = 'Selected IDs'): void
    {
        ?>
        <section class="sc-migration-bulk">
            <div class="sc-page-heading"><div><p class="sc-eyebrow">Bulk editor</p><h1><?php echo sc_m_escape($title); ?></h1></div></div>
            <form class="sc-form sc-bulk-form" action="<?php echo sc_m_escape($action); ?>" method="post" data-sc-bulk-form data-selected-name="<?php echo sc_m_escape($selectedName); ?>">
                <input type="hidden" name="<?php echo sc_m_escape($selectedName); ?>" value="[]" data-sc-selected>
                <input type="hidden" name="bouquets_selected" value="[]">
                <input type="hidden" name="category_id_type" value="SET">
                <input type="hidden" name="bouquets_type" value="SET">
                <input type="hidden" name="server_type" value="SET">
                <input type="hidden" name="server_tree_data" value="[]">
                <input type="hidden" name="od_tree_data" value="[]">
                <input type="hidden" name="on_demand[]" value="">
                <section class="sc-form-section">
                    <p class="sc-section-copy">Choose the records to change. Only selected IDs are submitted; an empty selection is a no-op.</p>
                    <label><?php echo sc_m_escape($selectionLabel); ?><textarea rows="2" placeholder="e.g. 12, 18" data-sc-selected-input></textarea></label>
                </section>
                <section class="sc-form-section"><div class="sc-form-grid">
                    <?php foreach ($fields as $field):
                        $type = $field['type'] ?? 'text';
                        $name = $field['name'];
                        $valueName = $field['value_name'] ?? $name;
                        $hasApply = ($field['apply'] ?? true) !== false;
                        $disabled = $hasApply ? ' disabled' : '';
                        $dataValue = $hasApply ? ' data-sc-value' : '';
                    ?>
                        <label>
                            <?php if ($hasApply): ?><input type="checkbox" name="<?php echo sc_m_escape('c_' . $name); ?>" value="1" data-sc-enable="<?php echo sc_m_escape($valueName); ?>"> Apply <?php echo sc_m_escape($field['label']); ?><?php else: ?><span><?php echo sc_m_escape($field['label']); ?></span><?php endif; ?>
                            <?php if ($type === 'select'): ?>
                                <select name="<?php echo sc_m_escape($valueName); ?>"<?php echo $disabled . $dataValue; ?>><?php foreach (($field['options'] ?? []) as $optionValue => $optionLabel): ?><option value="<?php echo sc_m_escape($optionValue); ?>"<?php echo (string) ($field['default'] ?? '') === (string) $optionValue ? ' selected' : ''; ?>><?php echo sc_m_escape($optionLabel); ?></option><?php endforeach; ?></select>
                            <?php elseif ($type === 'textarea' || substr($valueName, -2) === '[]'): ?>
                                <textarea name="<?php echo sc_m_escape($valueName); ?>" rows="3"<?php echo $disabled . $dataValue; ?> placeholder="<?php echo sc_m_escape($field['placeholder'] ?? ''); ?>"></textarea>
                            <?php elseif ($type === 'checkbox'): ?>
                                <input type="checkbox" name="<?php echo sc_m_escape($valueName); ?>" value="1"<?php echo $disabled . $dataValue; ?>>
                            <?php else: ?>
                                <input type="<?php echo sc_m_escape($type); ?>" name="<?php echo sc_m_escape($valueName); ?>"<?php echo $disabled . $dataValue; ?> placeholder="<?php echo sc_m_escape($field['placeholder'] ?? ''); ?>">
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div></section>
                <div class="sc-form-error" data-sc-form-error hidden></div><div class="sc-form-actions"><button class="sc-button sc-button-primary" type="submit">Apply to selected</button></div>
            </form>
        </section>
        <?php
    }
}
