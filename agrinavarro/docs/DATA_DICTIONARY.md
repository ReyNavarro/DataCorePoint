# Live Supabase Data Dictionary

Generated from the connected Supabase PostgreSQL catalog on 2026-11-05. It predates the applied lookup-normalization migration and is not a current schema contract. Regenerate this metadata after the latest migrations before submission. This file contains no application rows or credentials.

## `activity_logs`

| Column | PostgreSQL type | Nullable | Default |
| --- | --- | --- | --- |
| `log_id` | integer | No | `nextval('activity_logs_log_id_seq'::regclass)` |
| `user_id` | integer | Yes | `-` |
| `log_action` | text | No | `-` |
| `created_at` | timestamp without time zone | Yes | `CURRENT_TIMESTAMP` |

## `system_settings`

| Column | PostgreSQL type | Nullable | Default |
| --- | --- | --- | --- |
| `setting_id` | integer | No | `nextval('system_settings_setting_id_seq'::regclass)` |
| `setting_key` | character varying(50) | No | `-` |
| `setting_value` | text | No | `-` |

## `users`

| Column | PostgreSQL type | Nullable | Default |
| --- | --- | --- | --- |
| `user_id` | integer | No | `nextval('users_user_id_seq'::regclass)` |
| `username` | character varying(50) | No | `-` |
| `password_hash` | character varying(255) | No | `-` |
| `role_id` | integer | Yes | `-` |

## `roles`

| Column | PostgreSQL type | Nullable | Default |
| --- | --- | --- | --- |
| `role_id` | integer | No | `nextval('roles_role_id_seq'::regclass)` |
| `role_name` | character varying(50) | No | `-` |

## `farmers`

| Column | PostgreSQL type | Nullable | Default |
| --- | --- | --- | --- |
| `farmer_id` | integer | No | `nextval('farmers_farmer_id_seq'::regclass)` |
| `first_name` | character varying(50) | No | `-` |
| `last_name` | character varying(50) | No | `-` |
| `contact_number` | character varying(20) | No | `-` |
| `address` | character varying(150) | No | `-` |
| `date_registered` | date | No | `-` |
| `is_deleted` | boolean | Yes | `false` |

## `farms`

| Column | PostgreSQL type | Nullable | Default |
| --- | --- | --- | --- |
| `farm_id` | integer | No | `nextval('farms_farm_id_seq'::regclass)` |
| `farmer_id` | integer | Yes | `-` |
| `farm_name` | character varying(100) | No | `-` |
| `location` | character varying(150) | No | `-` |
| `area_hectares` | numeric(8,2) | No | `-` |
| `soil_type` | character varying(50) | No | `-` |
| `irrigation_type` | character varying(50) | No | `-` |
| `soil_id` | integer | Yes | `-` |
| `irrigation_id` | integer | Yes | `-` |
| `is_deleted` | boolean | Yes | `false` |

## `soil_types`

| Column | PostgreSQL type | Nullable | Default |
| --- | --- | --- | --- |
| `soil_id` | integer | No | `nextval('soil_types_soil_id_seq'::regclass)` |
| `soil_name` | character varying(50) | No | `-` |
| `created_at` | timestamp without time zone | Yes | `CURRENT_TIMESTAMP` |
| `updated_at` | timestamp without time zone | Yes | `CURRENT_TIMESTAMP` |

## `irrigation_types`

| Column | PostgreSQL type | Nullable | Default |
| --- | --- | --- | --- |
| `irrigation_id` | integer | No | `nextval('irrigation_types_irrigation_id_seq'::regclass)` |
| `irrigation_name` | character varying(50) | No | `-` |
| `created_at` | timestamp without time zone | Yes | `CURRENT_TIMESTAMP` |
| `updated_at` | timestamp without time zone | Yes | `CURRENT_TIMESTAMP` |

## `crops`

| Column | PostgreSQL type | Nullable | Default |
| --- | --- | --- | --- |
| `crop_id` | integer | No | `nextval('crops_crop_id_seq'::regclass)` |
| `crop_name` | character varying(80) | No | `-` |
| `variety` | character varying(80) | No | `-` |
| `crop_category` | character varying(50) | No | `-` |
| `growing_period_days` | integer | No | `-` |
| `recommended_season` | character varying(30) | No | `-` |
| `category_id` | integer | Yes | `-` |
| `season_id` | integer | Yes | `-` |
| `is_deleted` | boolean | Yes | `false` |

## `crop_categories`

| Column | PostgreSQL type | Nullable | Default |
| --- | --- | --- | --- |
| `category_id` | integer | No | `nextval('crop_categories_category_id_seq'::regclass)` |
| `category_name` | character varying(50) | No | `-` |
| `created_at` | timestamp without time zone | Yes | `CURRENT_TIMESTAMP` |
| `updated_at` | timestamp without time zone | Yes | `CURRENT_TIMESTAMP` |

## `seasons`

| Column | PostgreSQL type | Nullable | Default |
| --- | --- | --- | --- |
| `season_id` | integer | No | `nextval('seasons_season_id_seq'::regclass)` |
| `season_name` | character varying(30) | No | `-` |
| `created_at` | timestamp without time zone | Yes | `CURRENT_TIMESTAMP` |
| `updated_at` | timestamp without time zone | Yes | `CURRENT_TIMESTAMP` |

## `plantings`

| Column | PostgreSQL type | Nullable | Default |
| --- | --- | --- | --- |
| `planting_id` | integer | No | `nextval('plantings_planting_id_seq'::regclass)` |
| `farm_id` | integer | Yes | `-` |
| `crop_id` | integer | Yes | `-` |
| `planting_date` | date | No | `-` |
| `area_planted` | numeric(8,2) | No | `-` |
| `seed_quantity` | numeric(8,2) | No | `-` |
| `expected_harvest_date` | date | No | `-` |
| `status` | character varying(30) | No | `-` |
| `status_id` | integer | Yes | `-` |
| `is_deleted` | boolean | Yes | `false` |

## `planting_statuses`

| Column | PostgreSQL type | Nullable | Default |
| --- | --- | --- | --- |
| `status_id` | integer | No | `nextval('planting_statuses_status_id_seq'::regclass)` |
| `status_name` | character varying(30) | No | `-` |
| `created_at` | timestamp without time zone | Yes | `CURRENT_TIMESTAMP` |
| `updated_at` | timestamp without time zone | Yes | `CURRENT_TIMESTAMP` |

## `harvests`

| Column | PostgreSQL type | Nullable | Default |
| --- | --- | --- | --- |
| `harvest_id` | integer | No | `nextval('harvests_harvest_id_seq'::regclass)` |
| `planting_id` | integer | Yes | `-` |
| `harvest_date` | date | No | `-` |
| `quantity_kg` | numeric(10,2) | No | `-` |
| `selling_price_per_kg` | numeric(10,2) | No | `-` |
| `quality_grade` | character varying(30) | No | `-` |
| `remarks` | character varying(200) | No | `-` |
| `grade_id` | integer | Yes | `-` |
| `remark_id` | integer | Yes | `-` |
| `is_deleted` | boolean | Yes | `false` |

## `quality_grades`

| Column | PostgreSQL type | Nullable | Default |
| --- | --- | --- | --- |
| `grade_id` | integer | No | `nextval('quality_grades_grade_id_seq'::regclass)` |
| `grade_name` | character varying(30) | No | `-` |
| `created_at` | timestamp without time zone | Yes | `CURRENT_TIMESTAMP` |
| `updated_at` | timestamp without time zone | Yes | `CURRENT_TIMESTAMP` |

## `harvest_remarks`

| Column | PostgreSQL type | Nullable | Default |
| --- | --- | --- | --- |
| `remark_id` | integer | No | `nextval('harvest_remarks_remark_id_seq'::regclass)` |
| `remark_description` | character varying(100) | No | `-` |
| `created_at` | timestamp without time zone | Yes | `CURRENT_TIMESTAMP` |
| `updated_at` | timestamp without time zone | Yes | `CURRENT_TIMESTAMP` |

## Constraints

| Table | Name | Definition |
| --- | --- | --- |
| `activity_logs` | `activity_logs_user_id_fkey` | `FOREIGN KEY (user_id) REFERENCES users(user_id)` |
| `activity_logs` | `activity_logs_pkey` | `PRIMARY KEY (log_id)` |
| `crop_categories` | `crop_categories_pkey` | `PRIMARY KEY (category_id)` |
| `crop_categories` | `crop_categories_category_name_key` | `UNIQUE (category_name)` |
| `crops` | `crops_category_id_fkey` | `FOREIGN KEY (category_id) REFERENCES crop_categories(category_id)` |
| `crops` | `crops_season_id_fkey` | `FOREIGN KEY (season_id) REFERENCES seasons(season_id)` |
| `crops` | `crops_pkey` | `PRIMARY KEY (crop_id)` |
| `farmers` | `farmers_pkey` | `PRIMARY KEY (farmer_id)` |
| `farms` | `farms_farmer_id_fkey` | `FOREIGN KEY (farmer_id) REFERENCES farmers(farmer_id)` |
| `farms` | `farms_irrigation_id_fkey` | `FOREIGN KEY (irrigation_id) REFERENCES irrigation_types(irrigation_id)` |
| `farms` | `farms_soil_id_fkey` | `FOREIGN KEY (soil_id) REFERENCES soil_types(soil_id)` |
| `farms` | `farms_pkey` | `PRIMARY KEY (farm_id)` |
| `harvest_remarks` | `harvest_remarks_pkey` | `PRIMARY KEY (remark_id)` |
| `harvest_remarks` | `harvest_remarks_remark_description_key` | `UNIQUE (remark_description)` |
| `harvests` | `harvests_grade_id_fkey` | `FOREIGN KEY (grade_id) REFERENCES quality_grades(grade_id)` |
| `harvests` | `harvests_planting_id_fkey` | `FOREIGN KEY (planting_id) REFERENCES plantings(planting_id)` |
| `harvests` | `harvests_remark_id_fkey` | `FOREIGN KEY (remark_id) REFERENCES harvest_remarks(remark_id)` |
| `harvests` | `harvests_pkey` | `PRIMARY KEY (harvest_id)` |
| `irrigation_types` | `irrigation_types_pkey` | `PRIMARY KEY (irrigation_id)` |
| `irrigation_types` | `irrigation_types_irrigation_name_key` | `UNIQUE (irrigation_name)` |
| `planting_statuses` | `planting_statuses_pkey` | `PRIMARY KEY (status_id)` |
| `planting_statuses` | `planting_statuses_status_name_key` | `UNIQUE (status_name)` |
| `plantings` | `plantings_crop_id_fkey` | `FOREIGN KEY (crop_id) REFERENCES crops(crop_id)` |
| `plantings` | `plantings_farm_id_fkey` | `FOREIGN KEY (farm_id) REFERENCES farms(farm_id)` |
| `plantings` | `plantings_status_id_fkey` | `FOREIGN KEY (status_id) REFERENCES planting_statuses(status_id)` |
| `plantings` | `plantings_pkey` | `PRIMARY KEY (planting_id)` |
| `quality_grades` | `quality_grades_pkey` | `PRIMARY KEY (grade_id)` |
| `quality_grades` | `quality_grades_grade_name_key` | `UNIQUE (grade_name)` |
| `roles` | `roles_pkey` | `PRIMARY KEY (role_id)` |
| `seasons` | `seasons_pkey` | `PRIMARY KEY (season_id)` |
| `seasons` | `seasons_season_name_key` | `UNIQUE (season_name)` |
| `soil_types` | `soil_types_pkey` | `PRIMARY KEY (soil_id)` |
| `soil_types` | `soil_types_soil_name_key` | `UNIQUE (soil_name)` |
| `system_settings` | `system_settings_pkey` | `PRIMARY KEY (setting_id)` |
| `system_settings` | `system_settings_setting_key_key` | `UNIQUE (setting_key)` |
| `users` | `users_role_id_fkey` | `FOREIGN KEY (role_id) REFERENCES roles(role_id)` |
| `users` | `users_pkey` | `PRIMARY KEY (user_id)` |
| `users` | `users_username_key` | `UNIQUE (username)` |

Review this file after any schema migration. Supabase Row Level Security policies and grants are not represented by PostgreSQL table constraints and should be checked separately in the Supabase dashboard.
