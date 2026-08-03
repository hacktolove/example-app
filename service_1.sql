--
-- PostgreSQL database dump
--

\restrict K47TIVfnG19vXrmb5vvdAw8H11dE9wOxRBapN7kWNu7ANBZbR9uo53GfawDzo4q

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
-- Name: charge_log; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.charge_log (
    msisdn character varying(16),
    status_code character varying(4),
    reason_phrase character varying(64),
    trans_status character varying(8),
    amount double precision,
    trans_details text,
    trans_date date,
    trans_time time without time zone
);


ALTER TABLE public.charge_log OWNER TO fusionpbx;

--
-- Name: charge_log_retry; Type: TABLE; Schema: public; Owner: fusionpbx
--

CREATE TABLE public.charge_log_retry (
    msisdn character varying(16),
    status_code character varying(4),
    reason_phrase character varying(64),
    trans_status character varying(8),
    amount double precision,
    trans_details text,
    trans_date date,
    trans_time time without time zone,
    last_update_date date,
    last_update_time time without time zone
);


ALTER TABLE public.charge_log_retry OWNER TO fusionpbx;

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
-- Data for Name: charge_log; Type: TABLE DATA; Schema: public; Owner: fusionpbx
--

COPY public.charge_log (msisdn, status_code, reason_phrase, trans_status, amount, trans_details, trans_date, trans_time) FROM stdin;
\.


--
-- Data for Name: charge_log_retry; Type: TABLE DATA; Schema: public; Owner: fusionpbx
--

COPY public.charge_log_retry (msisdn, status_code, reason_phrase, trans_status, amount, trans_details, trans_date, trans_time, last_update_date, last_update_time) FROM stdin;
\.


--
-- Data for Name: profiles; Type: TABLE DATA; Schema: public; Owner: fusionpbx
--

COPY public.profiles (msisdn, package, language, channel, status, subs_date, subs_time, last_update_date, last_update_time, last_charge_date, last_charge_time) FROM stdin;
+249117386372	\N	\N	ivr	1	2026-06-15	18:20:22.208931	2026-06-15	18:20:22.208931	\N	\N
+249124706817	\N	\N	ivr	1	2026-06-18	03:44:20.801982	2026-06-18	03:44:20.801982	\N	\N
+249114186527	\N	\N	ivr	1	2026-06-21	13:09:23.05743	2026-06-21	13:09:23.05743	\N	\N
+249115035399	\N	\N	ivr	1	2026-06-21	13:54:33.067771	2026-06-21	13:54:33.067771	\N	\N
+249129268901	\N	\N	ivr	1	2026-07-01	09:49:12.157526	2026-07-01	09:49:12.157526	\N	\N
+249128198211	\N	\N	ivr	0	2026-06-21	10:41:49.304985	2026-07-01	18:38:52.062587	\N	\N
\.


--
-- Name: TABLE profiles; Type: ACL; Schema: public; Owner: fusionpbx
--

GRANT SELECT,INSERT,DELETE,UPDATE ON TABLE public.profiles TO peter;


--
-- PostgreSQL database dump complete
--

\unrestrict K47TIVfnG19vXrmb5vvdAw8H11dE9wOxRBapN7kWNu7ANBZbR9uo53GfawDzo4q

