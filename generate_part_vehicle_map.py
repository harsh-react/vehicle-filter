#!/usr/bin/env python3
import csv
import os
import sys
import io
import re
from datetime import datetime


def main(argv: list[str]) -> int:
    script_dir = os.path.dirname(os.path.abspath(__file__))
    default_input = os.path.join(script_dir, "csv", "t4p_new_data.csv")
    default_output = os.path.join(script_dir, "data", "part_vehicle_map.csv")

    in_path = argv[1] if len(argv) > 1 else default_input
    out_path = argv[2] if len(argv) > 2 else default_output

    if not os.path.isfile(in_path):
        print(f"Input CSV not found: {in_path}", file=sys.stderr)
        return 1

    os.makedirs(os.path.dirname(out_path), exist_ok=True)

    # Indices based on the provided sample header in t4p_new_data.csv
    IDX_VEHICLE_ID = 0
    IDX_BRAND = 2
    IDX_MODEL = 3
    IDX_LISTING = 4
    IDX_DATE_RANGE = 5
    IDX_PART_NUMBER = 10

    # Read and decode the CSV similarly to generate_tables.py for robustness
    enc_try = ["utf-8-sig", "cp1252", "latin-1"]
    decoded_text = None
    last_err = None
    for enc in enc_try:
        try:
            with open(in_path, "r", encoding=enc, newline="") as f:
                decoded_text = f.read()
            break
        except UnicodeDecodeError as e:
            last_err = e
            continue
    if decoded_text is None:
        raise last_err if last_err else UnicodeDecodeError("unknown", b"", 0, 1, "No working encoding")

    part_to_vehicle_ids: dict[str, set[int]] = {}
    # Map canonical vehicle signature to first-seen vehicle_id (to dedupe)
    signature_to_vehicle_id: dict[tuple[str, str, str, int, int], int] = {}

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

    def normalize_text(raw: str) -> str:
        if raw is None:
            return ""
        return re.sub(r"\s+", " ", str(raw).strip()).lower()

    sio = io.StringIO(decoded_text)
    reader = csv.reader(sio)
    try:
        header = next(reader)
    except StopIteration:
        print("Input CSV is empty.", file=sys.stderr)
        return 1

    for row in reader:
        if not row:
            continue
        needed_len = max(IDX_VEHICLE_ID, IDX_PART_NUMBER, IDX_BRAND, IDX_MODEL, IDX_LISTING, IDX_DATE_RANGE) + 1
        if len(row) < needed_len:
            row = row + [""] * (needed_len - len(row))

        veh_raw = (row[IDX_VEHICLE_ID] or "").strip()
        part_raw = (row[IDX_PART_NUMBER] or "").strip()
        brand = (row[IDX_BRAND] or "").strip()
        model = (row[IDX_MODEL] or "").strip()
        listing = (row[IDX_LISTING] or "").strip()
        date_range = (row[IDX_DATE_RANGE] or "").strip()

        if not veh_raw or not part_raw:
            continue

        # Normalize vehicle id to integer where possible
        try:
            vehicle_id = int(float(veh_raw))
        except ValueError:
            continue

        # Remap to canonical vehicle_id based on normalized signature
        year_from, year_to = parse_year_range(date_range)
        signature = (
            normalize_text(brand),
            normalize_text(model),
            normalize_text(listing),
            int(year_from),
            int(year_to),
        )
        kept_vehicle_id = signature_to_vehicle_id.get(signature)
        if kept_vehicle_id is None:
            kept_vehicle_id = vehicle_id
            signature_to_vehicle_id[signature] = kept_vehicle_id

        part_number = part_raw

        s = part_to_vehicle_ids.setdefault(part_number, set())
        s.add(kept_vehicle_id)

    # Write mapping CSV with comma-separated vehicle IDs per part number
    with open(out_path, "w", encoding="utf-8", newline="") as f:
        writer = csv.writer(f)
        writer.writerow(["part_number", "vehicle_ids"])  # vehicle_ids as comma-separated string
        for part_number in sorted(part_to_vehicle_ids.keys()):
            ids = sorted(part_to_vehicle_ids[part_number])
            writer.writerow([part_number, ", ".join(str(v) for v in ids)])

    print(f"Wrote: {out_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv))


