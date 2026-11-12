-- The application assigns the registration date automatically when a farmer is created.
ALTER TABLE public.farmers
    ALTER COLUMN date_registered SET DEFAULT CURRENT_DATE;
