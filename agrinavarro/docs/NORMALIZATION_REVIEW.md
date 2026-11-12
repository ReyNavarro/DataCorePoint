# Normalization review

This review compares the supplied `1st NORMAL FORM.docx` reference images with the current Supabase catalog. It treats the DOCX as supporting design material; the assignment requirements are listed separately in the project documentation.

## What the supplied document contains

The DOCX has 1NF, 2NF, and 3NF headings, sample table screenshots, and a Supabase schema diagram. The schema diagram has been copied to [the ERD page](ERD.md). The sample screenshots contain names and telephone numbers, so they are not copied into this repository.

## UNF to 1NF

A single spreadsheet-style record that repeats farmer, farm, crop, planting, and harvest details for each production entry would duplicate data and make updates inconsistent. The first normal form step is to keep fields atomic and identify each record with a primary key. The current schema uses separate primary keys such as `farmer_id`, `farm_id`, `crop_id`, `planting_id`, and `harvest_id`.

## 1NF to 2NF

The current entities use single-column primary keys. Their non-key fields therefore depend on the whole key; there are no composite-key partial dependencies in these main records. Related entities are separated: a farm references a farmer, a planting references a farm and crop, and a harvest references a planting.

## 2NF to 3NF

Reusable descriptions belong in lookup tables. The current model has `soil_types`, `irrigation_types`, `crop_categories`, `seasons`, `planting_statuses`, `quality_grades`, and `harvest_remarks`, and the schema includes foreign keys from the operational tables to these lookups. Application dropdowns submit the numeric IDs and display the descriptive labels.

The lookup-normalization migration has been applied to the connected Supabase project. A live catalog/data audit after the change found no legacy duplicate label columns and no missing required lookup IDs. The application dropdowns display descriptions and submit the numeric foreign-key values.

## Post-migration checks

The lookup migration was applied to the connected Supabase database. The follow-up live audit found no duplicate legacy label columns and no rows missing required lookup IDs. For future schema changes:

1. Back up the Supabase project.
2. Run migrations against a staging database first.
3. Verify primary keys, foreign keys, required fields, uniqueness, and business-rule checks after each change.
4. Regenerate the data dictionary and update the ERD from the live schema.
5. Retest create, update, archive, restore, reports, and relationship behavior before deployment.

The migration at `../supabase/migrations/20261105_normalize_lookup_fields.sql` includes preflight checks for missing IDs, label disagreements, and invalid numeric/date values. It has been applied. The current data dictionary predates this migration and needs regeneration before final submission.