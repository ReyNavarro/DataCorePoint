-- Review and run manually in the Supabase SQL Editor after taking a backup.
-- This is intentionally not run by the application or deployment process.
-- The preflight block stops before any ALTER TABLE if data has become invalid.

BEGIN;

DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM farms WHERE farmer_id IS NULL OR soil_id IS NULL OR irrigation_id IS NULL)
       OR EXISTS (SELECT 1 FROM crops WHERE category_id IS NULL)
       OR EXISTS (SELECT 1 FROM plantings WHERE farm_id IS NULL OR crop_id IS NULL OR status_id IS NULL)
       OR EXISTS (SELECT 1 FROM harvests WHERE planting_id IS NULL OR grade_id IS NULL OR remark_id IS NULL)
       OR EXISTS (SELECT 1 FROM users WHERE role_id IS NULL) THEN
        RAISE EXCEPTION 'Migration stopped: a required relationship has no lookup ID.';
    END IF;

    IF EXISTS (
        SELECT 1 FROM farms f
        JOIN soil_types s ON s.soil_id = f.soil_id
        JOIN irrigation_types i ON i.irrigation_id = f.irrigation_id
        WHERE (f.soil_type <> '' AND f.soil_type <> s.soil_name)
           OR (f.irrigation_type <> '' AND f.irrigation_type <> i.irrigation_name)
    ) OR EXISTS (
        SELECT 1 FROM crops c
        JOIN crop_categories cc ON cc.category_id = c.category_id
        LEFT JOIN seasons s ON s.season_id = c.season_id
        WHERE (c.crop_category <> '' AND c.crop_category <> cc.category_name)
           OR (c.recommended_season <> '' AND c.recommended_season <> COALESCE(s.season_name, ''))
    ) OR EXISTS (
        SELECT 1 FROM plantings p
        JOIN planting_statuses s ON s.status_id = p.status_id
        WHERE p.status <> '' AND p.status <> s.status_name
    ) OR EXISTS (
        SELECT 1 FROM harvests h
        JOIN quality_grades g ON g.grade_id = h.grade_id
        JOIN harvest_remarks r ON r.remark_id = h.remark_id
        WHERE (h.quality_grade <> '' AND h.quality_grade <> g.grade_name)
           OR (h.remarks <> '' AND h.remarks <> r.remark_description)
    ) THEN
        RAISE EXCEPTION 'Migration stopped: a legacy label disagrees with its lookup row.';
    END IF;

    IF EXISTS (SELECT 1 FROM farms WHERE area_hectares < 0)
       OR EXISTS (SELECT 1 FROM crops WHERE growing_period_days < 0)
       OR EXISTS (SELECT 1 FROM plantings WHERE area_planted < 0 OR seed_quantity < 0 OR expected_harvest_date < planting_date)
       OR EXISTS (SELECT 1 FROM harvests WHERE quantity_kg <= 0 OR selling_price_per_kg < 0) THEN
        RAISE EXCEPTION 'Migration stopped: a numeric or date value violates the proposed checks.';
    END IF;

    IF EXISTS (
        SELECT 1 FROM users
        GROUP BY lower(username)
        HAVING count(*) > 1
    ) THEN
        RAISE EXCEPTION 'Migration stopped: usernames must be unique regardless of letter case.';
    END IF;
END $$;

-- Preserve existing foreign keys and make mandatory references explicit.
ALTER TABLE farms ALTER COLUMN farmer_id SET NOT NULL;
ALTER TABLE farms ALTER COLUMN soil_id SET NOT NULL;
ALTER TABLE farms ALTER COLUMN irrigation_id SET NOT NULL;
ALTER TABLE crops ALTER COLUMN category_id SET NOT NULL;
ALTER TABLE plantings ALTER COLUMN farm_id SET NOT NULL;
ALTER TABLE plantings ALTER COLUMN crop_id SET NOT NULL;
ALTER TABLE plantings ALTER COLUMN status_id SET NOT NULL;
ALTER TABLE harvests ALTER COLUMN planting_id SET NOT NULL;
ALTER TABLE harvests ALTER COLUMN grade_id SET NOT NULL;
ALTER TABLE harvests ALTER COLUMN remark_id SET NOT NULL;
ALTER TABLE users ALTER COLUMN role_id SET NOT NULL;

-- Lookup IDs are the single source of truth after this point.
ALTER TABLE farms DROP COLUMN soil_type, DROP COLUMN irrigation_type;
ALTER TABLE crops DROP COLUMN crop_category, DROP COLUMN recommended_season;
ALTER TABLE plantings DROP COLUMN status;
ALTER TABLE harvests DROP COLUMN quality_grade, DROP COLUMN remarks;

ALTER TABLE farms ADD CONSTRAINT farms_area_nonnegative CHECK (area_hectares >= 0);
ALTER TABLE crops ADD CONSTRAINT crops_growing_period_nonnegative CHECK (growing_period_days >= 0);
ALTER TABLE plantings
    ADD CONSTRAINT plantings_area_nonnegative CHECK (area_planted >= 0),
    ADD CONSTRAINT plantings_seed_nonnegative CHECK (seed_quantity >= 0),
    ADD CONSTRAINT plantings_expected_after_start CHECK (expected_harvest_date >= planting_date);
ALTER TABLE harvests
    ADD CONSTRAINT harvests_quantity_positive CHECK (quantity_kg > 0),
    ADD CONSTRAINT harvests_price_nonnegative CHECK (selling_price_per_kg >= 0);

-- Store creation and modification times for the core records and user accounts.
ALTER TABLE farmers
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW();
ALTER TABLE farms
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW();
ALTER TABLE crops
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW();
ALTER TABLE plantings
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW();
ALTER TABLE harvests
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW();
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW();

CREATE UNIQUE INDEX IF NOT EXISTS users_username_case_insensitive_uidx
    ON users (lower(username));

CREATE OR REPLACE FUNCTION public.set_updated_at()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$;

DO $$
DECLARE
    table_name TEXT;
BEGIN
    FOREACH table_name IN ARRAY ARRAY['farmers','farms','crops','plantings','harvests','users'] LOOP
        EXECUTE format('DROP TRIGGER IF EXISTS %I ON public.%I', table_name || '_set_updated_at', table_name);
        EXECUTE format(
            'CREATE TRIGGER %I BEFORE UPDATE ON public.%I FOR EACH ROW EXECUTE FUNCTION public.set_updated_at()',
            table_name || '_set_updated_at',
            table_name
        );
    END LOOP;
END $$;

COMMIT;
