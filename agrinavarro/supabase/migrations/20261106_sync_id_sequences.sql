-- Advance serial/identity sequences that fell behind manually imported IDs.
-- This only moves a sequence forward; it does not modify table rows.
DO $$
DECLARE
    item RECORD;
    sequence_name TEXT;
    maximum_id BIGINT;
    current_id BIGINT;
BEGIN
    FOR item IN
        SELECT table_schema, table_name, column_name
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND (column_default LIKE 'nextval(%' OR is_identity = 'YES')
    LOOP
        sequence_name := pg_get_serial_sequence(
            format('%I.%I', item.table_schema, item.table_name),
            item.column_name
        );

        IF sequence_name IS NULL THEN
            CONTINUE;
        END IF;

        EXECUTE format(
            'SELECT COALESCE(MAX(%I), 0) FROM %I.%I',
            item.column_name,
            item.table_schema,
            item.table_name
        ) INTO maximum_id;

        EXECUTE format('SELECT last_value FROM %s', sequence_name)
        INTO current_id;

        IF maximum_id > current_id THEN
            PERFORM setval(sequence_name::regclass, maximum_id, TRUE);
        END IF;
    END LOOP;
END $$;
