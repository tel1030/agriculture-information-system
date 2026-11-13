-- Optional, synthetic demonstration records. Run once after the initial schema migration.
-- No contact numbers or personal records from the assignment screenshots are used.
DO $$
DECLARE
    farmer_a BIGINT;
    farmer_b BIGINT;
    farmer_c BIGINT;
BEGIN
    INSERT INTO public.farmers(first_name,last_name,contact_number,address,date_registered)
    VALUES ('Demo Farmer','One',NULL,'Sample District A',CURRENT_DATE - 90)
    RETURNING farmer_id INTO farmer_a;
    INSERT INTO public.farmers(first_name,last_name,contact_number,address,date_registered)
    VALUES ('Demo Farmer','Two',NULL,'Sample District B',CURRENT_DATE - 60)
    RETURNING farmer_id INTO farmer_b;
    INSERT INTO public.farmers(first_name,last_name,contact_number,address,date_registered)
    VALUES ('Demo Farmer','Three',NULL,'Sample District C',CURRENT_DATE - 30)
    RETURNING farmer_id INTO farmer_c;

    INSERT INTO public.farms(farmer_id,farm_name,location,area_hectares,soil_type_id,irrigation_type_id) VALUES
    (farmer_a,'Demo Green Farm','Sample District A',2.50,(SELECT soil_type_id FROM public.soil_types WHERE soil_type_name='Loam'),(SELECT irrigation_type_id FROM public.irrigation_types WHERE irrigation_type_name='Irrigated')),
    (farmer_b,'Demo Creek Farm','Sample District B',4.00,(SELECT soil_type_id FROM public.soil_types WHERE soil_type_name='Clay Loam'),(SELECT irrigation_type_id FROM public.irrigation_types WHERE irrigation_type_name='Rainfed')),
    (farmer_c,'Demo Orchard Farm','Sample District C',3.25,(SELECT soil_type_id FROM public.soil_types WHERE soil_type_name='Sandy Loam'),(SELECT irrigation_type_id FROM public.irrigation_types WHERE irrigation_type_name='Drip Irrigation'));

    INSERT INTO public.crops(crop_name,variety,category_option_id,growing_period_days,season_option_id) VALUES
    ('Rice','Demo variety',(SELECT option_id FROM public.crop_options WHERE option_type='category' AND option_label='Cereal'),115,(SELECT option_id FROM public.crop_options WHERE option_type='season' AND option_label='Wet Season')),
    ('Tomato','Demo variety',(SELECT option_id FROM public.crop_options WHERE option_type='category' AND option_label='Vegetable'),90,(SELECT option_id FROM public.crop_options WHERE option_type='season' AND option_label='Dry Season')),
    ('Mango','Demo variety',(SELECT option_id FROM public.crop_options WHERE option_type='category' AND option_label='Fruit'),150,(SELECT option_id FROM public.crop_options WHERE option_type='season' AND option_label='Year Round'));

    INSERT INTO public.plantings(farm_id,crop_id,planting_date,area_planted,seed_quantity,expected_harvest_date,status_id)
    SELECT f.farm_id,c.crop_id,CURRENT_DATE - 75,1.20,24.00,CURRENT_DATE + 40,(SELECT status_id FROM public.planting_statuses WHERE status_name='Growing')
    FROM public.farms f JOIN public.crops c ON c.crop_name='Rice' WHERE f.farm_name='Demo Green Farm';
    INSERT INTO public.plantings(farm_id,crop_id,planting_date,area_planted,seed_quantity,expected_harvest_date,status_id)
    SELECT f.farm_id,c.crop_id,CURRENT_DATE - 105,0.80,8.00,CURRENT_DATE - 15,(SELECT status_id FROM public.planting_statuses WHERE status_name='Harvested')
    FROM public.farms f JOIN public.crops c ON c.crop_name='Tomato' WHERE f.farm_name='Demo Creek Farm';
    INSERT INTO public.plantings(farm_id,crop_id,planting_date,area_planted,seed_quantity,expected_harvest_date,status_id)
    SELECT f.farm_id,c.crop_id,CURRENT_DATE - 20,0.60,6.00,CURRENT_DATE + 70,(SELECT status_id FROM public.planting_statuses WHERE status_name='Planned')
    FROM public.farms f JOIN public.crops c ON c.crop_name='Mango' WHERE f.farm_name='Demo Orchard Farm';

    INSERT INTO public.harvests(planting_id,harvest_date,quantity_kg,selling_price_per_kg,quality_option_id,remark_option_id)
    SELECT p.planting_id,CURRENT_DATE - 12,760.00,28.50,
           (SELECT option_id FROM public.harvest_options WHERE option_type='grade' AND option_label='Grade A'),
           1
    FROM public.plantings p JOIN public.crops c USING(crop_id) WHERE c.crop_name='Tomato';
END;
$$;
