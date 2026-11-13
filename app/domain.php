<?php

declare(strict_types=1);

// Beginner Note: These definitions keep page labels and writeable column names in one reviewed place.
// [LEARNING EXPLANATION] JOINs connect a record to its related table so screens can show farmer, farm, crop, and lookup names instead of database IDs.
function module_definitions(): array
{
    return [
        'farmers' => [
            'title' => 'Farmers',
            'table' => 'farmers',
            'key' => 'farmer_id',
            'keyExpr' => 'f.farmer_id',
            'label' => 'farmer_id',
            'search' => ['f.first_name', 'f.last_name', 'f.contact_number', 'f.address'],
            'filters' => [],
            'from' =>
                'farmers f LEFT JOIN (SELECT farmer_id, COUNT(*) farm_count FROM farms GROUP BY farmer_id) fc ON fc.farmer_id=f.farmer_id',
            'columns' => [
                'farmer_id' => 'ID',
                'full_name' => 'Farmer',
                'contact_number' => 'Contact',
                'address' => 'Address',
                'date_registered' => 'Registered',
                'farm_count' => 'Farms',
            ],
            'sorts' => [
                'farmer_id' => 'f.farmer_id',
                'full_name' => 'f.last_name',
                'address' => 'f.address',
                'date_registered' => 'f.date_registered',
            ],
            'fields' => [
                'first_name' => ['First name', 'text', true, 50],
                'last_name' => ['Last name', 'text', true, 50],
                'contact_number' => ['Contact number (optional)', 'text', false, 20],
                'address' => ['Address', 'text', true, 150],
                'date_registered' => ['Registration date', 'date', true, 0],
            ],
            'select' =>
                'f.farmer_id, f.first_name, f.last_name, (f.first_name || \' \' || f.last_name) AS full_name, f.contact_number, f.address, f.date_registered, COALESCE(fc.farm_count,0) AS farm_count',
            'recordSelect' =>
                'farmer_id, first_name, last_name, contact_number, address, date_registered',
        ],
        'farms' => [
            'title' => 'Farms',
            'table' => 'farms',
            'key' => 'farm_id',
            'keyExpr' => 'fm.farm_id',
            'label' => 'farm_name',
            'search' => ['fm.farm_name', 'fm.location', 'fr.first_name', 'fr.last_name'],
            'filters' => [
                'irrigation_type_id' => [
                    'label' => 'Irrigation',
                    'column' => 'fm.irrigation_type_id',
                    'query' =>
                        'SELECT irrigation_type_id id, irrigation_type_name name FROM public.irrigation_types WHERE is_active ORDER BY name',
                ],
            ],
            'from' =>
                'farms fm JOIN farmers fr ON fr.farmer_id=fm.farmer_id JOIN soil_types st ON st.soil_type_id=fm.soil_type_id JOIN irrigation_types it ON it.irrigation_type_id=fm.irrigation_type_id',
            'columns' => [
                'farm_id' => 'ID',
                'farm_name' => 'Farm',
                'owner_name' => 'Farmer',
                'location' => 'Location',
                'area_hectares' => 'Area (ha)',
                'soil_type_name' => 'Soil',
                'irrigation_type_name' => 'Irrigation',
            ],
            'sorts' => [
                'farm_id' => 'fm.farm_id',
                'farm_name' => 'fm.farm_name',
                'owner_name' => 'fr.last_name',
                'location' => 'fm.location',
                'area_hectares' => 'fm.area_hectares',
            ],
            'fields' => [
                'farmer_id' => [
                    'Farmer',
                    'lookup',
                    true,
                    0,
                    'SELECT farmer_id id, first_name || \' \' || last_name name FROM public.farmers ORDER BY last_name,first_name',
                ],
                'farm_name' => ['Farm name', 'text', true, 100],
                'location' => ['Location', 'text', true, 150],
                'area_hectares' => ['Area in hectares', 'decimal', true, 0],
                'soil_type_id' => [
                    'Soil type',
                    'lookup',
                    true,
                    0,
                    'SELECT soil_type_id id, soil_type_name name FROM public.soil_types WHERE is_active ORDER BY name',
                ],
                'irrigation_type_id' => [
                    'Irrigation type',
                    'lookup',
                    true,
                    0,
                    'SELECT irrigation_type_id id, irrigation_type_name name FROM public.irrigation_types WHERE is_active ORDER BY name',
                ],
            ],
            'select' =>
                'fm.farm_id, fm.farm_name, fm.location, fm.area_hectares, fr.first_name || \' \' || fr.last_name owner_name, fr.farmer_id, st.soil_type_name, fm.soil_type_id, it.irrigation_type_name, fm.irrigation_type_id',
            'recordSelect' =>
                'farm_id, farmer_id, farm_name, location, area_hectares, soil_type_id, irrigation_type_id',
        ],
        'crops' => [
            'title' => 'Crops',
            'table' => 'crops',
            'key' => 'crop_id',
            'keyExpr' => 'c.crop_id',
            'label' => 'crop_name',
            'search' => ['c.crop_name', 'c.variety', 'cat.option_label', 'sea.option_label'],
            'filters' => [
                'category_option_id' => [
                    'label' => 'Category',
                    'column' => 'c.category_option_id',
                    'query' =>
                        "SELECT option_id id, option_label name FROM public.crop_options WHERE option_type='category' AND is_active ORDER BY name",
                ],
            ],
            'from' =>
                'crops c JOIN crop_options cat ON cat.option_id=c.category_option_id AND cat.option_type=\'category\' JOIN crop_options sea ON sea.option_id=c.season_option_id AND sea.option_type=\'season\'',
            'columns' => [
                'crop_id' => 'ID',
                'crop_name' => 'Crop',
                'variety' => 'Variety',
                'category_name' => 'Category',
                'growing_period_days' => 'Growing days',
                'season_name' => 'Season',
            ],
            'sorts' => [
                'crop_id' => 'c.crop_id',
                'crop_name' => 'c.crop_name',
                'variety' => 'c.variety',
                'growing_period_days' => 'c.growing_period_days',
            ],
            'fields' => [
                'crop_name' => ['Crop name', 'text', true, 80],
                'variety' => ['Variety', 'text', true, 80],
                'category_option_id' => [
                    'Category',
                    'lookup',
                    true,
                    0,
                    "SELECT option_id id, option_label name FROM public.crop_options WHERE option_type='category' AND is_active ORDER BY name",
                ],
                'growing_period_days' => ['Growing period (days)', 'integer', true, 0],
                'season_option_id' => [
                    'Recommended season',
                    'lookup',
                    true,
                    0,
                    "SELECT option_id id, option_label name FROM public.crop_options WHERE option_type='season' AND is_active ORDER BY name",
                ],
            ],
            'select' =>
                'c.crop_id,c.crop_name,c.variety,cat.option_label category_name,c.category_option_id,c.growing_period_days,sea.option_label season_name,c.season_option_id',
            'recordSelect' =>
                'crop_id,crop_name,variety,category_option_id,growing_period_days,season_option_id',
        ],
        'plantings' => [
            'title' => 'Plantings',
            'table' => 'plantings',
            'key' => 'planting_id',
            'keyExpr' => 'p.planting_id',
            'label' => 'planting_id',
            'search' => [
                'fm.farm_name',
                'c.crop_name',
                'c.variety',
                'fr.first_name',
                'fr.last_name',
            ],
            'filters' => [
                'status_id' => [
                    'label' => 'Status',
                    'column' => 'p.status_id',
                    'query' =>
                        'SELECT status_id id,status_name name FROM public.planting_statuses WHERE is_active ORDER BY name',
                ],
                'farm_id' => [
                    'label' => 'Farm',
                    'column' => 'p.farm_id',
                    'query' => 'SELECT farm_id id,farm_name name FROM public.farms ORDER BY name',
                ],
            ],
            'from' =>
                'plantings p JOIN farms fm ON fm.farm_id=p.farm_id JOIN farmers fr ON fr.farmer_id=fm.farmer_id JOIN crops c ON c.crop_id=p.crop_id JOIN planting_statuses ps ON ps.status_id=p.status_id',
            'columns' => [
                'planting_id' => 'ID',
                'farm_name' => 'Farm',
                'owner_name' => 'Farmer',
                'crop_display' => 'Crop',
                'planting_date' => 'Planted',
                'area_planted' => 'Area (ha)',
                'seed_quantity' => 'Seed (kg)',
                'expected_harvest_date' => 'Expected harvest',
                'status_name' => 'Status',
            ],
            'sorts' => [
                'planting_id' => 'p.planting_id',
                'farm_name' => 'fm.farm_name',
                'crop_display' => 'c.crop_name',
                'planting_date' => 'p.planting_date',
                'expected_harvest_date' => 'p.expected_harvest_date',
            ],
            'fields' => [
                'farm_id' => [
                    'Farm',
                    'lookup',
                    true,
                    0,
                    'SELECT fm.farm_id id, fm.farm_name || \' — \' || fr.last_name name FROM public.farms fm JOIN public.farmers fr USING(farmer_id) ORDER BY fm.farm_name',
                ],
                'crop_id' => [
                    'Crop',
                    'lookup',
                    true,
                    0,
                    'SELECT crop_id id, crop_name || \' — \' || variety name FROM public.crops ORDER BY crop_name',
                ],
                'planting_date' => ['Planting date', 'date', true, 0],
                'area_planted' => ['Area planted (ha)', 'decimal', true, 0],
                'seed_quantity' => ['Seed quantity (kg)', 'decimal', true, 0],
                'expected_harvest_date' => ['Expected harvest date', 'date', true, 0],
                'status_id' => [
                    'Status',
                    'lookup',
                    true,
                    0,
                    'SELECT status_id id,status_name name FROM public.planting_statuses WHERE is_active ORDER BY status_id',
                ],
            ],
            'select' =>
                'p.planting_id,fm.farm_name,fr.first_name || \' \' || fr.last_name owner_name,c.crop_name || \' — \' || c.variety crop_display,p.planting_date,p.area_planted,p.seed_quantity,p.expected_harvest_date,ps.status_name,p.farm_id,p.crop_id,p.status_id',
            'recordSelect' =>
                'planting_id,farm_id,crop_id,planting_date,area_planted,seed_quantity,expected_harvest_date,status_id',
        ],
        'harvests' => [
            'title' => 'Harvests',
            'table' => 'harvests',
            'key' => 'harvest_id',
            'keyExpr' => 'h.harvest_id',
            'label' => 'harvest_id',
            'search' => [
                'fm.farm_name',
                'c.crop_name',
                'c.variety',
                'fr.first_name',
                'fr.last_name',
            ],
            'filters' => [
                'quality_option_id' => [
                    'label' => 'Quality',
                    'column' => 'h.quality_option_id',
                    'query' =>
                        "SELECT option_id id,option_label name FROM public.harvest_options WHERE option_type='grade' AND is_active ORDER BY name",
                ],
            ],
            'from' =>
                'harvests h JOIN plantings p ON p.planting_id=h.planting_id JOIN farms fm ON fm.farm_id=p.farm_id JOIN farmers fr ON fr.farmer_id=fm.farmer_id JOIN crops c ON c.crop_id=p.crop_id JOIN harvest_options q ON q.option_id=h.quality_option_id AND q.option_type=\'grade\' JOIN harvest_options r ON r.option_id=h.remark_option_id AND r.option_type=\'remark\'',
            'columns' => [
                'harvest_id' => 'ID',
                'farm_name' => 'Farm',
                'crop_display' => 'Crop',
                'harvest_date' => 'Date',
                'quantity_kg' => 'Quantity (kg)',
                'selling_price_per_kg' => 'Price/kg',
                'quality_name' => 'Quality',
                'remark_name' => 'Remark',
                'estimated_value' => 'Estimated value',
            ],
            'sorts' => [
                'harvest_id' => 'h.harvest_id',
                'farm_name' => 'fm.farm_name',
                'harvest_date' => 'h.harvest_date',
                'quantity_kg' => 'h.quantity_kg',
            ],
            'fields' => [
                'planting_id' => [
                    'Planting / farm / crop',
                    'lookup',
                    true,
                    0,
                    'SELECT p.planting_id id, p.planting_id || \' — \' || fm.farm_name || \' — \' || c.crop_name name FROM public.plantings p JOIN public.farms fm USING(farm_id) JOIN public.crops c USING(crop_id) ORDER BY p.planting_id DESC',
                ],
                'harvest_date' => ['Harvest date', 'date', true, 0],
                'quantity_kg' => ['Quantity (kg)', 'decimal', true, 0],
                'selling_price_per_kg' => [
                    'Selling price per kg (optional)',
                    'decimal_optional',
                    false,
                    0,
                ],
                'quality_option_id' => [
                    'Quality grade',
                    'lookup',
                    true,
                    0,
                    "SELECT option_id id,option_label name FROM public.harvest_options WHERE option_type='grade' AND is_active ORDER BY name",
                ],
                'remark_option_id' => [
                    'Harvest remark',
                    'lookup',
                    true,
                    0,
                    "SELECT option_id id,option_label name FROM public.harvest_options WHERE option_type='remark' AND is_active ORDER BY option_id",
                ],
            ],
            'select' =>
                'h.harvest_id,fm.farm_name,c.crop_name || \' — \' || c.variety crop_display,h.harvest_date,h.quantity_kg,h.selling_price_per_kg,q.option_label quality_name,r.option_label remark_name,(h.quantity_kg*h.selling_price_per_kg) estimated_value,h.planting_id,h.quality_option_id,h.remark_option_id',
            'recordSelect' =>
                'harvest_id,planting_id,harvest_date,quantity_kg,selling_price_per_kg,quality_option_id,remark_option_id',
        ],
    ];
}

function lookup_options(string $sql): array
{
    return db()->query($sql)->fetchAll();
}

function build_module_values(array $definition): array
{
    // [LEARNING EXPLANATION] HTML helps users, but it can be bypassed. These server checks validate the submitted text, numbers, dates, and lookup IDs again before SQL runs.
    $values = [];
    foreach ($definition['fields'] as $name => $field) {
        [$label, $type, $required, $limit] = $field;
        if ($type === 'lookup') {
            $id = filter_var($_POST[$name] ?? null, FILTER_VALIDATE_INT);
            if (!$id) {
                throw new InvalidArgumentException("Choose a valid {$label}.");
            }
            $allowed = lookup_options($field[4]);
            if (
                !in_array($id, array_map(static fn($option) => (int) $option['id'], $allowed), true)
            ) {
                throw new InvalidArgumentException("Choose an active {$label} from the list.");
            }
            $values[$name] = $id;
        } elseif ($type === 'date') {
            $values[$name] = date_value($name);
        } elseif ($type === 'integer') {
            $values[$name] = clean_number($name, $required, true, true);
        } elseif ($type === 'decimal' || $type === 'decimal_optional') {
            $values[$name] = clean_number(
                $name,
                $required,
                in_array($name, ['area_hectares', 'area_planted', 'quantity_kg'], true),
            );
        } else {
            $values[$name] = clean_text($name, (int) $limit, $required);
        }
    }
    if (
        isset($values['planting_date']) &&
        isset($values['expected_harvest_date']) &&
        $values['expected_harvest_date'] < $values['planting_date']
    ) {
        throw new InvalidArgumentException(
            'Expected harvest date must be on or after planting date.',
        );
    }
    if (isset($values['planting_id'], $values['harvest_date'])) {
        $check = db()->prepare('SELECT planting_date FROM public.plantings WHERE planting_id=:id');
        $check->execute(['id' => $values['planting_id']]);
        $plantingDate = $check->fetchColumn();
        if (!$plantingDate) {
            throw new InvalidArgumentException('Choose an existing planting record.');
        }
        if ($values['harvest_date'] < $plantingDate) {
            throw new InvalidArgumentException('Harvest date must be on or after planting date.');
        }
    }
    foreach (
        [
            'area_hectares' => 999999.99,
            'area_planted' => 999999.99,
            'seed_quantity' => 999999.99,
            'quantity_kg' => 99999999.99,
            'selling_price_per_kg' => 99999999.99,
        ]
        as $column => $maximum
    ) {
        if (isset($values[$column]) && $values[$column] > $maximum) {
            throw new InvalidArgumentException("The value for {$column} is too large.");
        }
    }
    if (isset($values['contact_number']) && $values['contact_number'] === '') {
        $values['contact_number'] = null;
    }
    return $values;
}
