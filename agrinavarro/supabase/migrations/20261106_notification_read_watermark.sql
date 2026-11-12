-- Store the highest activity entry each user has read.
ALTER TABLE public.users
    ADD COLUMN IF NOT EXISTS last_notification_read_id BIGINT NOT NULL DEFAULT 0;

-- Treat activity that already exists as read when this feature is first enabled.
UPDATE public.users
SET last_notification_read_id = COALESCE((SELECT MAX(log_id) FROM public.activity_logs), 0)
WHERE last_notification_read_id = 0;
