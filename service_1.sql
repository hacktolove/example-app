--
-- PostgreSQL database dump
--

\restrict Swjl9e5mLNq74KESjdzvUXTr5m7A847g7DZOh5LVTPZZA8l1XwlftvJNl5JvOy0

-- Dumped from database version 18.2 (Ubuntu 18.2-1.pgdg22.04+1)
-- Dumped by pg_dump version 18.2 (Ubuntu 18.2-1.pgdg22.04+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: profiles; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.profiles (
    msisdn character varying(16),
    package character varying(8),
    language character varying(8),
    channel character varying(8),
    status smallint,
    subs_date date,
    subs_time time without time zone,
    last_update_date date,
    last_update_time time without time zone,
    last_charge_date date,
    last_charge_time time without time zone
);


ALTER TABLE public.profiles OWNER TO fusionpbx;

--
-- PostgreSQL database dump complete
--

\unrestrict Swjl9e5mLNq74KESjdzvUXTr5m7A847g7DZOh5LVTPZZA8l1XwlftvJNl5JvOy0
