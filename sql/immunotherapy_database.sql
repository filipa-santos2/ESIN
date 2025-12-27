-- =========================
--  ESIN project
--  Allergy Immunotherapy Clinic DB
--  SQLite
-- =========================

-- Domain glossary (clinical context):
-- AIT (Allergen Immunotherapy): treatment for allergic disease using repeated exposure to allergen extracts.
-- AITPlan: the prescribed treatment plan (route, protocols, dates, status) for a given patient.
-- Product: a physical vial/extract unit dispensed by a manufacturer and used across multiple administrations.
-- Visit: any scheduled encounter between a doctor and a patient (superclass for Consultation / AllergenTesting / Administration).
-- AllergenTesting: a visit where diagnostic tests are performed; per-allergen outcomes are stored in AllergenTestingResult.
-- PatientSensitization: current clinical “summary” of sensitization to an allergen (separate from historical test results).


-- Date/time conventions:
--   * dates stored as Julian day numbers (REAL)
--   * datetimes stored as Unix timestamps (INTEGER)



PRAGMA foreign_keys = ON;

-- Tables dropped in reverse dependency order to respect foreign key constraints --

DROP TABLE IF EXISTS AdverseEvent;
DROP TABLE IF EXISTS Administration;
DROP TABLE IF EXISTS AllergenTestingResult;
DROP TABLE IF EXISTS AllergenTesting;
DROP TABLE IF EXISTS Consultation;
DROP TABLE IF EXISTS Visit;
DROP TABLE IF EXISTS Product;
DROP TABLE IF EXISTS Manufacturer;
DROP TABLE IF EXISTS AITAllergen;
DROP TABLE IF EXISTS AITPlan;
DROP TABLE IF EXISTS PatientSensitization;
DROP TABLE IF EXISTS Allergen;
DROP TABLE IF EXISTS MedicalHistory;
DROP TABLE IF EXISTS ICD11;
DROP TABLE IF EXISTS Patient;
DROP TABLE IF EXISTS Doctor;


-- =========================================================
-- Core actors
-- =========================================================
CREATE TABLE Doctor (
    doctor_id INTEGER PRIMARY KEY,
    full_name TEXT NOT NULL,
    license_no INTEGER NOT NULL UNIQUE,
    specialty TEXT NOT NULL CHECK (specialty IN ('Immunoallergology','Pulmonology','Pediatrics','Otolaryngology',
  'Dermatology','General_Practice','Internal_Medicine')),
    email TEXT NOT NULL UNIQUE CHECK (email LIKE '%@%'),
    phone TEXT NOT NULL UNIQUE
    );


CREATE TABLE Patient (
    patient_id INTEGER PRIMARY KEY,
    full_name TEXT NOT NULL,
    birth_date REAL NOT NULL, --julian--
    sex TEXT NOT NULL CHECK (sex IN ('M','F','X')),
    email TEXT NOT NULL CHECK (email LIKE '%@%'),
    phone TEXT NOT NULL
    );

    -- In the Patient table the columns "email" and "phone" are not unique to cover for
    -- the possibility of one caregiver being responsible for several patients, for example:
    --      > a parent with 2 or more children;
    --      > a daughter that is taking care of two elderly parents;
    --      > a care home that has several patients under its care.

-- -------------------------
-- International Classification of Diseases 11th edition (ICD11) catalogue
-- -------------------------
CREATE TABLE ICD11 (
    icd11_code TEXT PRIMARY KEY,
    diagnosis TEXT NOT NULL UNIQUE
    );
    -- World Health Organization. (2022). ICD-11: International classification of diseases (11th revision). https://icd.who.int/

-- -------------------------
-- Medical History
-- (each antecedent belongs to exactly one patient + one doctor + one ICD11 code)
-- -------------------------
CREATE TABLE MedicalHistory (
    antecedent_id INTEGER PRIMARY KEY,
    onset_date REAL NOT NULL, --julian--
    resolution_date REAL CHECK (resolution_date IS NULL OR resolution_date >= onset_date), --julian--
    status TEXT NOT NULL CHECK (status IN ('active','inactive','resolved','past')),
    icd11_code TEXT REFERENCES ICD11(icd11_code) NOT NULL,
    patient_id INTEGER REFERENCES Patient(patient_id) NOT NULL,
    doctor_id INTEGER REFERENCES Doctor(doctor_id) NOT NULL
    );

    -- MedicalHistory.status classification:
    -- active   : currently present and clinically relevant; requires follow-up or treatment
    -- inactive : not currently symptomatic or treated, but still clinically relevant
    -- resolved : episode has ended while under follow-up with the current medical provider;
    --            no further follow-up is required
    -- past     : historical problem no longer active nor clinically relevant;
    --            the episode began and ended before contact with the current medical provider


-- -------------------------
-- Allergen catalogue based on the World Health Organization &  International Union of Immunological Societies (WHO/IUIS) allergen nomenclature 
-- -------------------------
CREATE TABLE Allergen (
    who_iuis_code TEXT PRIMARY KEY,
    species TEXT NOT NULL,
    common_name TEXT NOT NULL,
    biochemical_name TEXT NOT NULL,
    category TEXT NOT NULL CHECK (category IN ('aeroallergen','food','drug','hymenoptera','contact','other'))
    );
     -- Marsh, D. G., Platts-Mills, T. A. E., Aalberse, R., Aas, K., Baker, J., Bindslev-Jensen, C., … (for the WHO/IUIS Allergen Nomenclature Sub-Committee). (2014). Update of the WHO/IUIS Allergen Nomenclature Database based on analysis of allergen sequences. Allergy, 69(4), 413-419. https://doi.org/10.1111/all.12348

-- -------------------------
-- PatientSensitization
-- NOTE: PatientSensitization stores the current/most clinically relevant status per patient+allergen (summary, editable over time).
-- AllergenTestingResult stores time-stamped diagnostic results (history).
-- -------------------------
CREATE TABLE PatientSensitization (
    patient_id INTEGER REFERENCES Patient(patient_id) NOT NULL,
    who_iuis_code TEXT REFERENCES Allergen(who_iuis_code) NOT NULL,
    status TEXT NOT NULL CHECK (status IN ( 'negative','active','uncertain','AIT_resolved','spontaneously_resolved')),
    PRIMARY KEY (patient_id, who_iuis_code)
    );
    -- PatientSensitization.status reflects current clinical relevance (not the raw test result).


-- =========================================================
-- Product vs AITPlan:
-- AITPlan = prescription-level intent; Product = concrete dispensed vial (serial_number) used during administrations.
-- =========================================================
CREATE TABLE AITPlan (
    plan_id INTEGER PRIMARY KEY,
    route TEXT NOT NULL CHECK (route IN ('subcutaneous','intramuscular','sublingual','oral')),
    build_up_protocol TEXT NOT NULL CHECK (build_up_protocol IN ('standard','rush','semi-rush','ultra-rush','continuous')),
    maintenance_protocol TEXT NOT NULL CHECK (maintenance_protocol IN ('standard','extended-interval','shortened-interval')),
    start_date REAL NOT NULL, --Julian--
    end_date REAL CHECK (end_date IS NULL OR end_date >= start_date), --Julian--
    status TEXT NOT NULL CHECK (status IN ('not_started','build_up','maintenance','concluded','cancelled','lost_follow_up')),
    patient_id INTEGER REFERENCES Patient(patient_id) NOT NULL,
    doctor_id INTEGER REFERENCES Doctor(doctor_id) NOT NULL
    );
    -- AITPlan.status reflects treatment lifecycle (e.g., not_started/build_up/maintenance/completed/cancelled/lost_follow_up).


CREATE TABLE AITAllergen (
    plan_id INTEGER REFERENCES AITPlan(plan_id) NOT NULL,
    who_iuis_code TEXT REFERENCES Allergen(who_iuis_code) NOT NULL,
    start_dose_ug REAL NOT NULL CHECK (start_dose_ug > 0),
    target_dose_ug REAL NOT NULL CHECK (target_dose_ug >= start_dose_ug),
    PRIMARY KEY (plan_id, who_iuis_code)
    );

-- =========================================================
-- Manufacturer + Product (vials)
-- =========================================================
CREATE TABLE Manufacturer (
    manufacturer_id TEXT PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    country TEXT NOT NULL,
    contact_email TEXT NOT NULL UNIQUE CHECK (contact_email LIKE '%@%'),
    phone TEXT NOT NULL UNIQUE
    );

-- =========================================================
-- Product vs AITPlan:
-- AITPlan = prescription-level intent; Product = concrete dispensed vial (serial_number) used during administrations.
-- =========================================================
CREATE TABLE Product (
    serial_number INTEGER PRIMARY KEY,
    brand TEXT NOT NULL,
    concentration REAL NOT NULL CHECK (concentration > 0),
    no_allergens INTEGER NOT NULL CHECK (no_allergens BETWEEN 1 AND 6),
    price_eur INTEGER NOT NULL CHECK (price_eur >= 0),
    plan_id INTEGER REFERENCES AITPlan(plan_id) NOT NULL,
    manufacturer_id TEXT REFERENCES Manufacturer(manufacturer_id) NOT NULL
    );

-- =========================================================
-- Visit superclass + three subclasses: Consultation, AllergenTesting, Administration
-- Visit types: Consultation = clinical review; AllergenTesting = diagnostic testing; Administration = AIT dose given.
-- =========================================================
CREATE TABLE Visit (
    visit_id INTEGER PRIMARY KEY,
    doctor_id INTEGER REFERENCES Doctor(doctor_id) NOT NULL,
    patient_id INTEGER REFERENCES Patient(patient_id) NOT NULL,
    datetime_scheduled INTEGER NOT NULL, --unix--
    datetime_start INTEGER, --unix--
    datetime_end INTEGER CHECK (datetime_end IS NULL OR datetime_end >= datetime_start), --unix--
    UNIQUE (doctor_id, datetime_scheduled),
    UNIQUE (patient_id, datetime_scheduled)
    );
    -- in the table Visit the pair (doctor_id, datetime_scheduled) is UNIQUE to prevent prevent double booking in the same time slot for the same doctor
    -- this same logic applies to UNIQUE (patient_id, datetime_scheduled)

-- -------------------------
-- Consultation = clinical review;
-- (subclass of Visit)
-- -------------------------
CREATE TABLE Consultation (
    visit_id INTEGER PRIMARY KEY,
    subspecialty TEXT NOT NULL,
    FOREIGN KEY (visit_id) REFERENCES Visit(visit_id) ON DELETE CASCADE
    );

-- -------------------------
-- AllergenTesting = diagnostic testing
-- (subclass of Visit)
-- Note: AllergenTestingResult stores time-stamped diagnostic results (history).
-- PatientSensitization stores the current/most clinically relevant status per patient+allergen (summary, editable over time).
-- -------------------------
CREATE TABLE AllergenTesting (
    visit_id INTEGER PRIMARY KEY,
    FOREIGN KEY (visit_id) REFERENCES Visit(visit_id) ON DELETE CASCADE
    );

CREATE TABLE AllergenTestingResult (
    visit_id INTEGER NOT NULL REFERENCES AllergenTesting(visit_id) ON DELETE CASCADE, 
    who_iuis_code TEXT NOT NULL REFERENCES Allergen(who_iuis_code),
    test_type TEXT NOT NULL CHECK  (test_type IN (
        'skin_prick_test',
        'intradermal_test',
        'patch_test','specific_IgE',
        'component_resolved_diagnostics',
        'oral_food_challenge',
        'drug_provocation_test',
        'basophil_activation_test')),
    test_result TEXT NOT NULL CHECK (test_result IN ('positive','negative','inconclusive')),
    PRIMARY KEY (visit_id, who_iuis_code) 
    );
    -- Exactly one test recorded per allergen per AllergenTesting visit.
    -- Good clinical parctice recommends that further investigations for the same allergen should be performed later in time and recorded as new AllergenTesting visits (new visit_id).

-- -------------------------
-- Administration = AIT dose given
-- (subclass of Visit)
-- -------------------------
CREATE TABLE Administration (
    visit_id INTEGER PRIMARY KEY,
    session_no INTEGER NOT NULL CHECK (session_no > 0),
    phase TEXT NOT NULL CHECK (phase IN ('build_up','maintenance')),
    administration_site TEXT NOT NULL CHECK (administration_site IN (
        'left_upper_arm',
        'right_upper_arm',
        'left_forearm',
        'right_forearm',
        'sublingual',
        'oral',
        'other')),
    observation_minutes INTEGER NOT NULL CHECK (observation_minutes > 0),
    dose_ml REAL NOT NULL CHECK (dose_ml > 0),
    serial_number INTEGER REFERENCES Product(serial_number) NOT NULL,
    FOREIGN KEY (visit_id) REFERENCES Visit(visit_id) ON DELETE CASCADE
    );

CREATE TABLE AdverseEvent (
    ae_id INTEGER PRIMARY KEY,
    type TEXT NOT NULL,
    onset_minutes INTEGER CHECK (onset_minutes IS NULL OR (onset_minutes >= 0 AND onset_minutes <= 1440)),
    outcome TEXT NOT NULL,
    visit_id INTEGER NOT NULL REFERENCES Administration(visit_id) ON DELETE CASCADE
    );


