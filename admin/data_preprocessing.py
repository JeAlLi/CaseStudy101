#!/usr/bin/env python3
"""
T-Valuate data preprocessing
============================
Reads  : Database_T-Value.xlsx  (one sheet per model: INNOVA, FORTUNER, VIOS, HILUX, WIGO, AVANZA)
Writes : clean_vehicle_listings.csv   -> upload this in admin/import_clean_data.php
         cleaning_report.xlsx         -> what was removed / fixed, and why

Usage  : python preprocess_tvalue.py [input.xlsx] [output_folder]
Needs  : pandas, openpyxl   (pip install pandas openpyxl)

Every rule is listed in the CONFIG block or in a clearly named function, so you
can change a threshold or a mapping without touching the logic.
"""
import re
import sys
import difflib
import datetime
from pathlib import Path

import numpy as np
import pandas as pd

# ─────────────────────────────────────────────────────────────────────────────
# CONFIG
# ─────────────────────────────────────────────────────────────────────────────
INPUT_FILE = "Database_T-Value.xlsx"
OUTPUT_DIR = "."
CURRENT_YEAR = datetime.date.today().year

# Same limits the system already uses (train_model.php / submit_listing.php)
PRICE_MIN, PRICE_MAX = 50_000, 3_000_000
MILEAGE_MAX = 300_000
YEAR_MIN = 2010
MAX_KM_PER_YEAR = 60_000          # more than this on average = implausible odometer
PRICE_RATIO_LOW, PRICE_RATIO_HIGH = 0.55, 1.80   # vs median of same model + year
MIN_GROUP_FOR_RATIO = 5           # need this many comparable listings to judge a price

# Models the system supports (predict.php / submit_listing.php)
SUPPORTED_MODELS = {"Vios", "Innova", "Fortuner", "Hilux", "Wigo", "Avanza"}
MODEL_MAP = {                     # alias (lower-case) -> system model
    "vios ativ": "Vios",
    "hilux conquest": "Hilux",
}
# These are different vehicles from the sheet they were typed into; keeping them would
# distort that model's prices.
DROP_MODELS = {"hilux tamaraw", "lite ace pickup", "veloz"}
# Rows whose description names one of these vehicles are dropped even if the model column says Hilux/Innova/etc.
DROP_IF_NOTES_MATCH = r"TAMARAW|LITE ?ACE|VELOZ|ZENIX"
MIN_YEAR_BY_MODEL = {"Wigo": 2013}          # from reference_weights.year_start
GAS_ONLY = {"Vios", "Wigo", "Avanza"}       # no diesel variant in the PH market

# Text values that mean "missing"
MISSING_TOKENS = {"", "NAN", "NONE_", "NOT SEEN", "NOT PROVIDED", "#ERROR!", "#N/A", "N/A", "NA", "NULL"}

# Database enums / limits (vehicle_listings)
TRANSMISSIONS = {"Automatic", "Manual", "CVT"}
FUELS = {"Gasoline", "Diesel", "Hybrid", "Electric"}
MAINTENANCE = {"Excellent", "Good", "Average", "Poor", "Excellent (Casa)"}
LISTING_TYPES = {"scraped", "bidding"}      # 'bidding' needs the enum change shown in the importer
LIMITS = {"color": 30, "location": 100, "accident_history": 50, "payment_methods": 100,
          "modifications": 255, "district": 100, "barangay": 100, "city": 100,
          "seller_name": 100, "seller_contact": 255, "warranty_status": 50}

# Columns written to the CSV (DB column names; id/date_added/predicted_value/risk_* are left to the DB)
OUT_COLUMNS = [
    "vehicle_type", "brand", "model", "year_manufactured", "mileage", "color", "transmission",
    "fuel_type", "modifications", "registration_status", "or_cr_ready", "fully_paid",
    "deed_of_sale_ready", "maintenance_history", "accident_history", "first_owner", "flooded",
    "asking_price", "price_negotiability", "payment_methods", "trade_in", "location", "city",
    "district", "barangay", "reason_for_selling", "warranty_status", "seller_notes", "status",
    "listing_type", "seller_name", "seller_contact", "verification_status",
]

# City aliases -> the name used in ph_locations.php / the sell form
CITY_MAP = {
    "Mabolo": "Cebu City", "Bicutan": "Taguig", "Global City": "Taguig", "Alabang": "Muntinlupa",
    "Balintawak": "Quezon City", "Quezon Avenue": "Quezon City", "Fairview": "Quezon City",
    "Tacloban": "Tacloban City", "Mandaue City": "Mandaue", "Iloilo": "Iloilo City",
    "Las Pinas": "Las Piñas", "Makati City": "Makati",
}
PROVINCES_IN_DISTRICT_COLUMN = {"Laguna", "Leyte", "Misamis Oriental", "Davao del Sur", "Cebu",
                                "Pampanga", "Bulacan"}
# Only these cities are split into districts in ph_locations.php; every other city is a flat barangay list
DISTRICT_CITIES = {"Manila", "Quezon City", "Caloocan", "Pasay", "Makati", "Valenzuela", "Cebu City", "Davao City"}
NUMBERED_BARANGAY_CITIES = {"Manila", "Caloocan", "Pasay"}      # these keep the "Barangay 495" form
# (city, barangay) -> district, exactly as ph_locations.php groups them
DISTRICT_OF = {
    ("Cebu City", "Mabolo"): "North",
    ("Davao City", "1-A"): "Poblacion",
    ("Davao City", "Lanang"): "Buhangin",
    ("Makati", "Poblacion"): "District 1",
    ("Quezon City", "Central"): "District 4",
    ("Manila", "Barangay 495"): "Sampaloc",
}
# Cities that exist in the system's dropdowns; anything else goes on the "review" sheet
SYSTEM_CITIES = {
    "Manila", "Quezon City", "Caloocan", "Pasay", "Makati", "Valenzuela", "Taguig", "Pasig", "Parañaque",
    "Las Piñas", "Muntinlupa", "Mandaluyong", "San Juan", "Marikina", "Malabon", "Navotas", "Pateros",
    "Bacoor", "Imus", "Dasmariñas", "General Trias", "Calamba", "Santa Rosa", "Biñan", "San Pedro",
    "Antipolo", "Cainta", "Taytay", "Angono", "Malolos", "Meycauayan", "Marilao", "San Fernando",
    "Angeles", "Cebu City", "Iloilo City", "Davao City", "Cagayan de Oro", "Zamboanga City",
    "General Santos", "Mandaue", "Lapu-Lapu", "Bacolod", "Cabuyao", "Tagaytay", "Bocaue",
}
ACRONYMS = {"NCR", "BGC", "VISMIN", "TFS", "LTO", "OR", "CR"}

# Canonical colour names (keys are UPPER-CASE after cleaning)
COLOR_CANON = {
    "SUPER WHITE": "Super White", "WHITE": "White", "FREEDOM WHITE": "Freedom White",
    "PLATINUM WHITE PEARL MICA": "Platinum White Pearl", "PLATINUM WHITE PEARL": "Platinum White Pearl",
    "WHITE PEARL CRYSTAL SHINE": "White Pearl Crystal Shine", "WHITE PEARL": "White Pearl",
    "SILVER METALLIC": "Silver Metallic", "SILVER": "Silver Metallic",
    "GRAY METALLIC": "Gray Metallic", "GRAY": "Gray Metallic",
    "DARK GRAY": "Dark Gray Metallic", "DARK GRAY METALLIC": "Dark Gray Metallic",
    "DARK STEEL MICA": "Dark Steel Mica",
    "ATTITUDE BLACK MICA": "Attitude Black Mica", "ATTITUDE BLACK": "Attitude Black Mica",
    "BLACK": "Black", "BLACK METALLIC": "Black", "SPARKLING BLACK PEARL": "Sparkling Black Pearl",
    "ALUMINA JADE METALLIC": "Alumina Jade Metallic", "ALUMINA JADE": "Alumina Jade Metallic",
    "RED": "Red", "RED MICA METALLIC": "Red Mica Metallic", "RED MICA": "Red Mica Metallic",
    "RED METALLIC": "Red Mica Metallic", "DARK RED": "Dark Red Mica", "DARK RED MICA METALLIC": "Dark Red Mica",
    "BLACKISH RED MICA": "Blackish Red Mica", "BLACKISH RED": "Blackish Red Mica",
    "SUPER RED V": "Super Red V", "EMOTIONAL RED": "Emotional Red", "CRIMSON SPARK RED": "Crimson Spark Red",
    "GRAYISH BLUE MICA METALLIC": "Grayish Blue Mica Metallic", "GRAYISH BLUE METALLIC": "Grayish Blue Mica Metallic",
    "GRAYISH BLUE": "Grayish Blue Mica Metallic", "DARK BLUE METALLIC": "Dark Blue Metallic",
    "GREENISH GUN METAL MICA METALLIC": "Greenish Gun Metal Mica", "GREENISH GUN MM METALLIC": "Greenish Gun Metal Mica",
    "GREENISH GUN METALLIC": "Greenish Gun Metal Mica", "GREENISH GUN MICA METALLIC": "Greenish Gun Metal Mica",
    "ORANGE METALLIC": "Orange Metallic", "ORANGE": "Orange Metallic", "CHAMPAGNE": "Champagne",
    "SILKY GOLD MICA": "Silky Gold Mica", "SILKY GOLD": "Silky Gold Mica",
    "BRONZE MICA METALLIC": "Bronze Mica Metallic", "BRONZE": "Bronze Mica Metallic",
    "THERMALYTE": "Thermalyte", "DARK BROWN METALLIC": "Dark Brown Metallic",
    "PHANTOM BROWN METALLIC": "Phantom Brown Metallic", "YELLOW": "Yellow", "YELLOW SE": "Yellow",
}
REASON_MAP = {
    "need a bigger car": "Need a bigger car", "need bigger car": "Need a bigger car",
    "need bigger unit": "Need a bigger car", "upgrade to suv": "Need a bigger car",
    "need funds": "Need funds", "need cash": "Need funds",
}
PLACEHOLDER_NOTE = "fresh unit, well maintained"
PLACEHOLDER_CONTACT = "09171234567"


# ─────────────────────────────────────────────────────────────────────────────
# Tracking helpers
# ─────────────────────────────────────────────────────────────────────────────
class Tracker:
    def __init__(self):
        self.removed = []      # list of DataFrames with a 'reason' column
        self.steps = []        # (step, rows_before, rows_after)
        self.fixes = {}        # rule -> number of cells/rows changed
        self.examples = {}     # rule -> a few (before, after) pairs

    def drop(self, df, mask, reason, step=None):
        gone = df[mask]
        if len(gone):
            g = gone.copy()
            g["reason"] = reason
            self.removed.append(g)
        out = df[~mask].copy()
        self.steps.append((step or reason, len(df), len(out)))
        return out

    def fix(self, rule, before, after):
        """Count cells where before != after (treating NaN == NaN)."""
        b = before.astype("object").where(before.notna(), None)
        a = after.astype("object").where(after.notna(), None)
        changed = [(x, y) for x, y in zip(b, a) if x != y]
        if changed:
            self.fixes[rule] = self.fixes.get(rule, 0) + len(changed)
            self.examples.setdefault(rule, changed[:3])


T = Tracker()


def blank(v):
    """True for None / NaN / pd.NA (pandas versions differ in which one they use for missing text)."""
    return v is None or v is pd.NA or (isinstance(v, float) and np.isnan(v))


def clean_text(v):
    """Trim, collapse whitespace, turn 'NOT SEEN' etc. into None."""
    if blank(v):
        return None
    s = re.sub(r"\s+", " ", str(v)).strip()
    return None if s.upper() in MISSING_TOKENS else s


def smart_title(s):
    if blank(s):
        return None
    out = []
    for w in s.split(" "):
        out.append(w.upper() if w.upper().strip(",") in ACRONYMS else w.title() if w.isupper() or w.islower() else w)
    return " ".join(out)


# ─────────────────────────────────────────────────────────────────────────────
# Cleaning steps
# ─────────────────────────────────────────────────────────────────────────────
def load(path):
    sheets = pd.read_excel(path, sheet_name=None, dtype=object)
    frames = []
    for name, d in sheets.items():
        d = d.copy()
        d.columns = [str(c).strip() for c in d.columns]
        d["_sheet"] = name
        d["_row"] = np.arange(len(d)) + 2          # Excel row number (header is row 1)
        d = d.dropna(how="all", subset=[c for c in d.columns if not c.startswith("_")])
        frames.append(d)
    df = pd.concat(frames, ignore_index=True)
    T.steps.append(("Loaded all sheets (blank rows ignored)", len(df), len(df)))
    for c in df.columns:
        if not c.startswith("_"):
            df[c] = df[c].map(clean_text)
    return df


def remove_placeholders(df):
    """Each sheet starts with the same template row (Juan Dela Cruz / 09171234567 / P550,000)."""
    m = (df["seller_notes"].fillna("").str.lower().str.startswith(PLACEHOLDER_NOTE)
         | (df["seller_contact"].fillna("") == PLACEHOLDER_CONTACT))
    return T.drop(df, m, "Template/placeholder row (same sample row copied into every sheet)")


def normalize_models(df):
    low = df["model"].fillna("").str.lower()
    in_notes = df["seller_notes"].fillna("").str.contains(DROP_IF_NOTES_MATCH, case=False, regex=True)
    df = T.drop(df, low.isin(DROP_MODELS) | in_notes.reindex(df.index, fill_value=False),
                "Model not supported by the system (Tamaraw / Lite Ace / Veloz / Zenix typed into another model's sheet)")
    before = df["model"].copy()
    df["model"] = df["model"].map(lambda m: MODEL_MAP.get(str(m).lower(), str(m).title()) if not blank(m) else m)
    T.fix("Model names standardised (e.g. Vios Ativ -> Vios)", before, df["model"])
    return T.drop(df, ~df["model"].isin(SUPPORTED_MODELS), "Model not in supported list")


def to_numbers(df):
    df["year_manufactured"] = pd.to_numeric(df["year_manufactured"], errors="coerce")
    df["mileage"] = pd.to_numeric(df["mileage"].astype("object").str.replace(",", "", regex=False), errors="coerce")
    df["asking_price"] = pd.to_numeric(df["asking_price"].astype("object").str.replace(r"[₱,\s]", "", regex=True),
                                       errors="coerce")
    df = T.drop(df, df["year_manufactured"].isna(), "Missing year")
    df = T.drop(df, df["mileage"].isna(), "Mileage missing ('NOT SEEN' / 'NOT PROVIDED' / blank)")
    df = T.drop(df, df["asking_price"].isna(), "Asking price missing")
    df["year_manufactured"] = df["year_manufactured"].astype(int)
    df["mileage"] = df["mileage"].round().astype(int)
    df["asking_price"] = df["asking_price"].round().astype(int)
    return df


def validity_rules(df):
    df = T.drop(df, df["asking_price"].lt(PRICE_MIN) | df["asking_price"].gt(PRICE_MAX),
                f"Price outside P{PRICE_MIN:,}-P{PRICE_MAX:,}")
    df = T.drop(df, df["year_manufactured"].lt(YEAR_MIN) | df["year_manufactured"].gt(CURRENT_YEAR),
                f"Year outside {YEAR_MIN}-{CURRENT_YEAR}")
    too_old = df.apply(lambda r: r["year_manufactured"] < MIN_YEAR_BY_MODEL.get(r["model"], 0), axis=1)
    df = T.drop(df, too_old, "Year earlier than the model's launch year")
    df = T.drop(df, df["mileage"].lt(0) | df["mileage"].gt(MILEAGE_MAX), f"Mileage above {MILEAGE_MAX:,} km")
    age = (CURRENT_YEAR - df["year_manufactured"]).clip(lower=1)
    return T.drop(df, (df["mileage"] / age).gt(MAX_KM_PER_YEAR),
                  f"Implausible mileage for age (> {MAX_KM_PER_YEAR:,} km/yr)")


def price_outliers(df):
    """Compare each market listing with the median of the same model + year."""
    med = df.groupby(["model", "year_manufactured"])["asking_price"].transform("median")
    n = df.groupby(["model", "year_manufactured"])["asking_price"].transform("count")
    ratio = df["asking_price"] / med
    is_bid = df["listing_type"].fillna("").str.lower().eq("bidding")   # auction minimum bids are low by design
    m = (~is_bid) & (n >= MIN_GROUP_FOR_RATIO) & ((ratio < PRICE_RATIO_LOW) | (ratio > PRICE_RATIO_HIGH))
    return T.drop(df, m, f"Price outlier (outside {PRICE_RATIO_LOW}x-{PRICE_RATIO_HIGH}x of same model+year median)")


def transmission_fuel(df):
    notes = df["seller_notes"].fillna("").str.upper()

    def tx(s):
        if re.search(r"\bCVT\b", s): return "CVT"
        if re.search(r"\bM/?T\b", s): return "Manual"
        if re.search(r"\bA/?T\b", s): return "Automatic"
        return None

    def fu(s):
        if re.search(r"\bDSL\b|DIESEL", s): return "Diesel"
        if re.search(r"\bGAS\b|GASOLINE", s): return "Gasoline"
        return None

    before = df["transmission"].copy()
    hint = notes.map(tx)
    df["transmission"] = df["transmission"].str.title().replace({"Cvt": "CVT"})
    df.loc[hint.notna(), "transmission"] = hint[hint.notna()]
    T.fix("Transmission corrected from the listing description (AT/MT/CVT)", before, df["transmission"])

    before = df["fuel_type"].copy()
    df["fuel_type"] = df["fuel_type"].str.title().replace({"Gas": "Gasoline"})
    hint = notes.map(fu)
    df.loc[hint.notna(), "fuel_type"] = hint[hint.notna()]
    T.fix("Fuel type corrected from the listing description", before, df["fuel_type"])

    df = T.drop(df, df["fuel_type"].eq("Hybrid") | df["fuel_type"].eq("Electric"),
                "Hybrid/Electric not supported by the system (form and model only handle Gas/Diesel)")
    df = T.drop(df, df["model"].isin(GAS_ONLY) & df["fuel_type"].eq("Diesel"), "Diesel on a gasoline-only model")
    df = T.drop(df, ~df["transmission"].isin(TRANSMISSIONS) | ~df["fuel_type"].isin(FUELS),
                "Invalid transmission or fuel type")
    return df


def categoricals(df):
    yn = {"yes": "Yes", "y": "Yes", "true": "Yes", "1": "Yes", "no": "No", "n": "No", "false": "No", "0": "No"}
    for col, default in [("first_owner", "No"), ("flooded", "No"), ("or_cr_ready", "Yes"),
                         ("fully_paid", "Yes"), ("deed_of_sale_ready", "Yes"), ("trade_in", "No")]:
        before = df[col].copy()
        df[col] = df[col].map(lambda v: default if blank(v) else yn.get(str(v).lower(), default))
        T.fix(f"{col}: blanks/variants set to Yes/No (default {default})", before, df[col])

    before = df["modifications"].copy()
    df["modifications"] = df["modifications"].fillna("Stock / None")
    T.fix("modifications: blank -> 'Stock / None'", before, df["modifications"])
    before = df["accident_history"].copy()
    df["accident_history"] = df["accident_history"].fillna("None")
    T.fix("accident_history: blank -> 'None'", before, df["accident_history"])
    df["registration_status"] = df["registration_status"].fillna("Updated")

    before = df["maintenance_history"].copy()
    df["maintenance_history"] = df["maintenance_history"].map(
        lambda v: "Average" if blank(v) else (v.title() if v.title() in MAINTENANCE else (v if v in MAINTENANCE else "Average")))
    T.fix("maintenance_history: invalid -> 'Average'", before, df["maintenance_history"])

    df["price_negotiability"] = df["price_negotiability"].map(
        lambda v: v if v in ("Negotiable", "Fixed Price") else "Fixed Price")
    df["payment_methods"] = df["payment_methods"].fillna("Cash")

    def warranty(v):
        if blank(v): return "No Warranty"
        if re.search(r"\d+\s*year", v, re.I): return f"With Warranty ({v})"
        return v if v in ("No Warranty", "With Warranty") else "No Warranty"
    before = df["warranty_status"].copy()
    df["warranty_status"] = df["warranty_status"].map(warranty)
    T.fix("warranty_status standardised", before, df["warranty_status"])

    before = df["reason_for_selling"].copy()
    df["reason_for_selling"] = df["reason_for_selling"].map(
        lambda v: None if blank(v) else REASON_MAP.get(v.lower(), v))
    T.fix("reason_for_selling: similar wording merged", before, df["reason_for_selling"])

    before = df["verification_status"].copy()
    df["verification_status"] = df["verification_status"].map(
        lambda v: "mechanic_verified" if not blank(v) and v.lower() in ("verified", "mechanic_verified") else "unverified")
    T.fix("verification_status -> mechanic_verified / unverified", before, df["verification_status"])

    before = df["listing_type"].copy()
    df["listing_type"] = df["listing_type"].map(lambda v: v.lower() if not blank(v) and v.lower() in LISTING_TYPES else "scraped")
    T.fix("listing_type validated", before, df["listing_type"])
    df["status"] = "approved"
    df["vehicle_type"] = "Car"
    df["brand"] = "Toyota"
    return df


def clean_color(raw):
    if blank(raw):
        return None
    s = raw.split("/")[0].strip().upper()                 # dual-tone: keep the main colour
    s = (s.replace("METALIC", "METALLIC").replace("GRYAISH", "GRAYISH").replace("GREY", "GRAY"))
    s = re.sub(r"\s+(\d+|I{1,3})$", "", s)               # trailing paint-code numbers: "BLACK 1", "SUPER WHITE II"
    s = re.sub(r"METALLI$", "METALLIC", s)
    if s.startswith("ATINUM WP"):
        s = "PLATINUM WHITE PEARL MICA"
    if s in COLOR_CANON:
        return COLOR_CANON[s]
    close = difflib.get_close_matches(s, list(COLOR_CANON), n=1, cutoff=0.80)   # typos / cut-off text
    if close:
        return COLOR_CANON[close[0]]
    return s.title()[:LIMITS["color"]]


def colours(df):
    before = df["color"].copy()
    df["color"] = df["color"].map(clean_color)
    T.fix("color: case, typos, paint codes and dual-tones standardised", before, df["color"])
    return df


def clean_contact(raw):
    if blank(raw):
        return None
    parts = [p.strip() for p in re.split(r"\s*/\s*", raw) if p.strip()]
    mobile, extras = None, []
    for p in parts:
        digits = re.sub(r"\D", "", p)
        if digits.startswith("63") and len(digits) == 12:
            digits = "0" + digits[2:]
        if mobile is None and re.fullmatch(r"09\d{9}", digits):
            mobile = digits
        else:
            extras.append(p)
    if mobile is None:
        return raw[:LIMITS["seller_contact"]]
    return mobile + (f" ({', '.join(extras)})" if extras else "")


def sellers(df):
    before = df["seller_contact"].copy()
    df["seller_contact"] = df["seller_contact"].map(clean_contact)
    T.fix("seller_contact: one 09XXXXXXXXX number (dashes/spaces removed), extras in brackets", before, df["seller_contact"])

    def name(v):
        if blank(v): return None
        if v.lower() == "automart.ph": return "Automart.ph"
        return v.title() if v.isupper() else v
    before = df["seller_name"].copy()
    df["seller_name"] = df["seller_name"].map(name)
    T.fix("seller_name: capitalisation standardised", before, df["seller_name"])
    return df


def clean_barangay(city, brgy):
    if blank(brgy):
        return None
    b = re.sub(r"^barangay\s+", "", brgy, flags=re.I)
    if city in NUMBERED_BARANGAY_CITIES and re.fullmatch(r"\d+[A-Za-z\-]*", b):
        return f"Barangay {b}"
    return b


def locations(df):
    before = df["city"].copy()
    def city_fix(r):
        c = r["city"]
        return c if blank(c) else CITY_MAP.get(c, c)
    df["city"] = df.apply(city_fix, axis=1)
    T.fix("city: aliases mapped to real cities (Mabolo->Cebu City, Bicutan->Taguig ...)", before, df["city"])

    new_b, new_d = [], []
    for _, r in df.iterrows():
        b = clean_barangay(r["city"], r["barangay"])
        d = None if blank(r["district"]) else r["district"]
        if d in PROVINCES_IN_DISTRICT_COLUMN:
            d = None                                         # that column held a province, not a district
        if b and d and d.lower() == b.lower():
            d = None                                         # district just repeated the barangay name
        d = DISTRICT_OF.get((r["city"], b), d if (r["city"], b) not in DISTRICT_OF else None)
        if b is None or r["city"] not in DISTRICT_CITIES:
            d = None
        new_b.append(b); new_d.append(d)
    b0, d0 = df["barangay"].copy(), df["district"].copy()
    df["barangay"], df["district"] = new_b, new_d
    T.fix("barangay: 'Barangay ' prefix removed (kept for Manila-style numbers)", b0, df["barangay"])
    T.fix("district: provinces/repeated names removed, real districts filled (ph_locations.php grouping)", d0, df["district"])

    before = df["location"].copy()
    df["location"] = df["location"].map(smart_title).fillna(df["city"])
    T.fix("location: capitalisation standardised", before, df["location"])
    return df


def tidy_text_and_lengths(df):
    df["seller_notes"] = df["seller_notes"].map(lambda v: None if blank(v) else v)
    for col, n in LIMITS.items():
        long = df[col].fillna("").str.len() > n
        if long.any():
            T.fixes[f"{col}: truncated to {n} characters"] = int(long.sum())
            df.loc[long, col] = df.loc[long, col].str[:n]
    return df


def deduplicate(df):
    key = [c for c in OUT_COLUMNS if c not in ("vehicle_type",)]
    return T.drop(df, df.duplicated(subset=key, keep="first"), "Exact duplicate row")


# ─────────────────────────────────────────────────────────────────────────────
# Report
# ─────────────────────────────────────────────────────────────────────────────
def write_report(path, raw_rows, clean):
    from openpyxl.styles import Font, PatternFill, Alignment
    removed = (pd.concat(T.removed, ignore_index=True) if T.removed else pd.DataFrame())
    if len(removed):
        removed = removed[["_sheet", "_row", "reason", "model", "year_manufactured", "mileage",
                           "asking_price", "color", "city", "seller_name", "seller_notes"]]
        removed.columns = ["Sheet", "Excel row", "Reason", "Model", "Year", "Mileage", "Price", "Color",
                           "City", "Seller", "Notes"]
        removed = removed.sort_values(["Reason", "Sheet", "Excel row"])
    by_reason = (removed.groupby("Reason").size().reset_index(name="Rows removed")
                 .sort_values("Rows removed", ascending=False) if len(removed) else pd.DataFrame())
    fixes = pd.DataFrame([(k, v, " -> ".join(map(repr, T.examples[k][0])) if k in T.examples else "")
                          for k, v in T.fixes.items()],
                         columns=["Fix applied", "Values changed (counted before later row removals)", "Example (before -> after)"])
    summary = pd.DataFrame({
        "Item": ["Rows read from the workbook", "Rows removed", "Rows in clean file",
                 "  of which market listings (scraped)", "  of which auction units (bidding)"],
        "Value": [raw_rows, raw_rows - len(clean), len(clean),
                  int((clean.listing_type == "scraped").sum()), int((clean.listing_type == "bidding").sum())]})
    per_model = clean.groupby("model").agg(rows=("model", "size"), min_price=("asking_price", "min"),
                                           median_price=("asking_price", "median"), max_price=("asking_price", "max"),
                                           median_mileage=("mileage", "median")).reset_index()
    review = (clean.loc[~clean["city"].isin(SYSTEM_CITIES), ["city", "district", "barangay", "location"]]
              .value_counts(dropna=False).reset_index(name="rows"))
    review.columns = ["city", "district", "barangay", "location", "rows"]

    with pd.ExcelWriter(path, engine="openpyxl") as xw:
        summary.to_excel(xw, sheet_name="Summary", index=False)
        by_reason.to_excel(xw, sheet_name="Summary", index=False, startrow=len(summary) + 3)
        per_model.to_excel(xw, sheet_name="Summary", index=False, startrow=len(summary) + len(by_reason) + 7)
        fixes.to_excel(xw, sheet_name="Fixes", index=False)
        (removed if len(removed) else pd.DataFrame({"info": ["nothing removed"]})).to_excel(xw, sheet_name="Removed rows", index=False)
        review.to_excel(xw, sheet_name="Cities to review", index=False)
        for ws in xw.book.worksheets:
            for row in ws.iter_rows():
                for c in row:
                    c.font = Font(name="Arial", size=10, bold=c.font.bold)
            for col in ws.columns:
                width = max(len(str(c.value)) if c.value is not None else 0 for c in col[:200])
                ws.column_dimensions[col[0].column_letter].width = min(max(width + 2, 10), 70)
            for c in ws[1]:
                c.font = Font(name="Arial", size=10, bold=True, color="FFFFFF")
                c.fill = PatternFill("solid", fgColor="1F3A5F")
                c.alignment = Alignment(vertical="center")
        ws = xw.book["Summary"]
        ws.cell(row=len(summary) + 3, column=1).value = "Rows removed, by reason"
        ws.cell(row=len(summary) + len(by_reason) + 7, column=1).value = "Clean data by model"
        for r in (len(summary) + 3, len(summary) + len(by_reason) + 7):
            for c in ws[r]:
                c.font = Font(name="Arial", size=10, bold=True)


# ─────────────────────────────────────────────────────────────────────────────
def main():
    src = Path(sys.argv[1] if len(sys.argv) > 1 else INPUT_FILE)
    out = Path(sys.argv[2] if len(sys.argv) > 2 else OUTPUT_DIR)
    out.mkdir(parents=True, exist_ok=True)

    df = load(src)
    raw_rows = len(df)

    df = remove_placeholders(df)
    df = normalize_models(df)
    df = to_numbers(df)
    df = transmission_fuel(df)
    df = categoricals(df)            # also standardises listing_type (needed by the price rule)
    df = validity_rules(df)
    df = price_outliers(df)
    df = colours(df)
    df = sellers(df)
    df = locations(df)
    df = tidy_text_and_lengths(df)
    df = deduplicate(df)

    clean = df[OUT_COLUMNS].sort_values(["model", "year_manufactured", "asking_price"]).reset_index(drop=True)
    csv_path = out / "clean_vehicle_listings.csv"
    clean.to_csv(csv_path, index=False, encoding="utf-8", na_rep="")
    write_report(out / "cleaning_report.xlsx", raw_rows, clean)

    print(f"Read {raw_rows} rows  ->  kept {len(clean)}  (removed {raw_rows - len(clean)})")
    for step, a, b in T.steps:
        if a != b:
            print(f"  -{a - b:>4}  {step}")
    print(f"Wrote {csv_path}")
    print(f"Wrote {out / 'cleaning_report.xlsx'}")


if __name__ == "__main__":
    main()