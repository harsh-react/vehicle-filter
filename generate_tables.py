#!/usr/bin/env python3
import csv
import os
import re
import sys
import io
from collections import OrderedDict, defaultdict
from datetime import datetime


def parse_year_range(raw: str) -> tuple[int, int]:
    if not raw:
        year = datetime.now().year
        return year, year
    text = str(raw)
    nums = re.findall(r"\d{4}", text)
    if not nums:
        year = datetime.now().year
        return year, year
    if len(nums) == 1:
        start = int(nums[0])
        end = datetime.now().year
        if end < start:
            end = start
        return start, end
    start = int(nums[0])
    end = int(nums[1])
    if end < start:
        start, end = end, start
    return start, end


def split_engines(raw: str) -> list[str]:
    if not raw:
        return []
    # Split on commas and slashes; keep content inside, trim whitespace
    parts = re.split(r"[,/]+", str(raw))
    cleaned = []
    for p in parts:
        t = re.sub(r"\s+", " ", p.strip())
        if t:
            cleaned.append(t)
    return cleaned


def normalize_text(raw: str) -> str:
    """Normalize free-text for deduping: trim, collapse spaces, lowercase."""
    if raw is None:
        return ""
    return re.sub(r"\s+", " ", str(raw).strip()).lower()


def main(argv: list[str]) -> int:
    # Defaults relative to this script
    script_dir = os.path.dirname(os.path.abspath(__file__))
    default_input = os.path.join(script_dir, "csv", "t4p_new_data.csv")
    default_out_dir = os.path.join(script_dir, "data")

    in_path = argv[1] if len(argv) > 1 else default_input
    out_dir = argv[2] if len(argv) > 2 else default_out_dir

    if not os.path.isfile(in_path):
        print(f"Input CSV not found: {in_path}", file=sys.stderr)
        return 1

    os.makedirs(out_dir, exist_ok=True)

    # Output file paths
    vehicle_base_path = os.path.join(out_dir, "vehicle_base.csv")
    engine_table_path = os.path.join(out_dir, "engine_table.csv")
    vehicle_engine_path = os.path.join(out_dir, "vehicle_engine.csv")

    # Data stores
    vehicles: dict[int, dict] = {}
    engine_code_to_id: OrderedDict[str, int] = OrderedDict()
    engine_code_canonical: dict[str, str] = {}
    vehicle_to_engine_ids: defaultdict[int, set[int]] = defaultdict(set)
    # Map a canonical vehicle signature to the first-seen vehicle_id to skip duplicates
    signature_to_vehicle_id: dict[tuple[str, str, str, int, int], int] = {}

    # Indices in the input CSV (based on header sample)
    IDX_VEHICLE_ID = 0
    IDX_BRAND = 2
    IDX_MODEL = 3
    IDX_LISTING = 4
    IDX_DATE_RANGE = 5
    IDX_ENGINE = 7

    # Read and parse input with encoding fallbacks (decode full file first)
    enc_try = ["utf-8-sig", "cp1252", "latin-1"]
    used_encoding = None
    last_err = None
    decoded_text = None
    for enc in enc_try:
        try:
            with open(in_path, "r", encoding=enc, newline="") as f:
                decoded_text = f.read()
            used_encoding = enc
            break
        except UnicodeDecodeError as e:
            last_err = e
            continue
    if decoded_text is None:
        raise last_err if last_err else UnicodeDecodeError("unknown", b"", 0, 1, "No working encoding")

    sio = io.StringIO(decoded_text)
    reader = csv.reader(sio)
    try:
        header = next(reader)
    except StopIteration:
        print("Input CSV is empty.", file=sys.stderr)
        return 1

    # Iterate rows
    for row_num, row in enumerate(reader, start=2):
        if not row:
            continue
        # Ensure row has enough columns
        needed_len = max(IDX_ENGINE, IDX_DATE_RANGE, IDX_LISTING, IDX_MODEL, IDX_BRAND, IDX_VEHICLE_ID) + 1
        if len(row) < needed_len:
            row = row + [""] * (needed_len - len(row))

        veh_raw = row[IDX_VEHICLE_ID].strip()
        if not veh_raw:
            continue
        try:
            vehicle_id = int(float(veh_raw))
        except ValueError:
            continue

        brand = row[IDX_BRAND].strip()
        model = row[IDX_MODEL].strip()
        listing = row[IDX_LISTING].strip()
        date_range = row[IDX_DATE_RANGE].strip()
        engines_raw = row[IDX_ENGINE].strip()

        year_from, year_to = parse_year_range(date_range)

        # Build a canonical signature to detect duplicates
        signature = (
            normalize_text(brand),
            normalize_text(model),
            normalize_text(listing),
            int(year_from),
            int(year_to),
        )

        # Use the first-seen vehicle_id for this signature
        kept_vehicle_id = signature_to_vehicle_id.get(signature)
        if kept_vehicle_id is None:
            kept_vehicle_id = vehicle_id
            signature_to_vehicle_id[signature] = kept_vehicle_id

        # Create or update the kept vehicle entry only
        if kept_vehicle_id not in vehicles:
            vehicles[kept_vehicle_id] = {
                "vehicle_id": kept_vehicle_id,
                "make": brand,
                "model": model,
                "listing": listing,
                "year_from": year_from,
                "year_to": year_to,
            }
        else:
            v = vehicles[kept_vehicle_id]
            if not v["make"] and brand:
                v["make"] = brand
            if not v["model"] and model:
                v["model"] = model
            if not v["listing"] and listing:
                v["listing"] = listing
            v["year_from"] = min(v["year_from"], year_from)
            v["year_to"] = max(v["year_to"], year_to)

        for eng in split_engines(engines_raw):
            key = eng.lower()
            if key not in engine_code_to_id:
                engine_id = len(engine_code_to_id) + 1
                engine_code_to_id[key] = engine_id
                engine_code_canonical[key] = eng
            engine_id = engine_code_to_id[key]
            vehicle_to_engine_ids[kept_vehicle_id].add(engine_id)

    # Write vehicle_base.csv
    with open(vehicle_base_path, "w", encoding="utf-8", newline="") as f:
        writer = csv.writer(f)
        writer.writerow(["vehicle_id", "make", "model", "listing", "year_from", "year_to"])
        # Sort by vehicle_id for stability
        for vid in sorted(vehicles.keys()):
            v = vehicles[vid]
            writer.writerow([
                v["vehicle_id"],
                v["make"],
                v["model"],
                v["listing"],
                float(v["year_from"]),
                float(v["year_to"]),
            ])

    # Write engine_table.csv
    with open(engine_table_path, "w", encoding="utf-8", newline="") as f:
        writer = csv.writer(f)
        writer.writerow(["engine_id", "engine_code"])
        for key, engine_id in engine_code_to_id.items():
            writer.writerow([engine_id, engine_code_canonical[key]])

    # Write vehicle_engine.csv
    with open(vehicle_engine_path, "w", encoding="utf-8", newline="") as f:
        writer = csv.writer(f)
        writer.writerow(["vehicle_id", "engine_id"])
        for vid in sorted(vehicle_to_engine_ids.keys()):
            for engine_id in sorted(vehicle_to_engine_ids[vid]):
                writer.writerow([vid, engine_id])

    print("Wrote:")
    print(f"  {vehicle_base_path}")
    print(f"  {engine_table_path}")
    print(f"  {vehicle_engine_path}")
    if used_encoding:
        print(f"Input encoding: {used_encoding}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv))


