import re
import random
import pandas as pd

# Set random seed for reproducible beautiful ratings and reviews
random.seed(42)

def clean_specialization(spec):
    if not isinstance(spec, str):
        return 'General Physician'
    spec = spec.replace('****&****', '&').strip()
    spec_lower = spec.lower()
    
    # 1. Gynecology / Obstetrics
    if any(keyword in spec_lower for keyword in ['gynae', 'gyne', 'obstetric', 'antenatal', 'maternal', 'pregnancy', 'infertility', 'reproductive', 'fetal', 'ivf', 'lactation']):
        return 'Gynecologist & Obstetrician'
    
    # 2. Pediatrics / Child Specialist
    elif any(keyword in spec_lower for keyword in ['pediatr', 'child', 'neonatal', 'baby', 'infant']):
        return 'Pediatrician'
    
    # 3. Cardiology
    elif any(keyword in spec_lower for keyword in ['cardio', 'heart', 'vascular']):
        return 'Cardiologist'
    
    # 4. Dermatology / Cosmetology
    elif any(keyword in spec_lower for keyword in ['dermat', 'skin', 'cosmeto']):
        return 'Dermatologist'
    
    # 5. Neurology
    elif any(keyword in spec_lower for keyword in ['neuro', 'brain']):
        return 'Neurologist'
    
    # 6. Orthopedics / Bone & Spine
    elif any(keyword in spec_lower for keyword in ['ortho', 'bone', 'spine', 'rheumat']):
        return 'Orthopedic Surgeon'
    
    # 7. Ophthalmology / Eye Specialist
    elif any(keyword in spec_lower for keyword in ['ophthal', 'eye']):
        return 'Ophthalmologist'
    
    # 8. Psychiatry
    elif any(keyword in spec_lower for keyword in ['psychiatr', 'mental', 'behavior', 'addiction', 'psychol']):
        return 'Psychiatrist'
    
    # 9. Dentistry / Oral Surgery
    elif any(keyword in spec_lower for keyword in ['dent', 'gum', 'root canal', 'oral', 'maxillofacial', 'implant', 'filling', 'conservative']):
        return 'Dentist & Dental Surgeon'
    
    # 10. Endocrinology / Diabetes
    elif any(keyword in spec_lower for keyword in ['endo', 'diabet', 'hormone', 'thyroid']):
        return 'Endocrinologist & Diabetologist'
    
    # 11. Gastroenterology
    elif any(keyword in spec_lower for keyword in ['gastro', 'liver', 'hepat', 'digestive']):
        return 'Gastroenterologist'
    
    # 12. Oncology
    elif any(keyword in spec_lower for keyword in ['oncol', 'cancer', 'tumor']):
        return 'Oncologist'
    
    # 13. Urology
    elif any(keyword in spec_lower for keyword in ['urolog', 'androlog', 'transplant']):
        return 'Urologist'
    
    # 14. Nephrology
    elif any(keyword in spec_lower for keyword in ['nephrol', 'kidney']):
        return 'Nephrologist'
    
    # 15. ENT Specialist
    elif any(keyword in spec_lower for keyword in ['ent', 'ear', 'nose', 'throat', 'otolaryngol']):
        return 'ENT Specialist'
    
    # 16. General Surgeon
    elif any(keyword in spec_lower for keyword in ['laparosco', 'colorect', 'surge', 'thoracic', 'plastic', 'trauma', 'wound']):
        return 'General Surgeon'
    
    # 17. Allergy & Immunology
    elif any(keyword in spec_lower for keyword in ['allergy', 'immunol']):
        return 'Allergist & Immunologist'
    
    # 18. Physiotherapy / Rehab
    elif any(keyword in spec_lower for keyword in ['physio', 'rehab', 'physical']):
        return 'Physiotherapist'
    
    # 19. Dietitian & Nutritionist
    elif any(keyword in spec_lower for keyword in ['diet', 'nutrition', 'weight']):
        return 'Dietitian & Nutritionist'
    
    # 20. General Physician / Family Medicine
    elif any(keyword in spec_lower for keyword in ['medicine', 'physician', 'family', 'general', 'macp', 'clinical', 'health', 'expert', 'consultant', 'specialist', 'primary']):
        return 'General Physician'
    
    return 'General Physician'

def escape_sql(val):
    if val is None or pd.isna(val):
        return 'NULL'
    # Escape single quotes
    escaped = str(val).replace("'", "''")
    return f"'{escaped}'"

def main():
    print("Step 1: Reading Excel file...")
    df = pd.read_excel('Enrich_Contacts_All_Doc_Update.xls')
    print(f"Loaded {len(df)} rows from Excel.")

    print("\nStep 2: Parsing existing hospitals from schema.sql...")
    hospitals = {}
    with open('sql/schema.sql', 'r', encoding='utf-8') as f:
        schema_content = f.read()

    # Find hospital insert patterns like (1205, 'Bhashantek Clinic and Diagnostic Center', ...
    matches = re.findall(r'\((\d+),\s*\'([^\'\n]+)\',\s*\'[^\'\n]+\'', schema_content)
    for h_id, h_name in matches:
        hospitals[h_name.lower().strip()] = int(h_id)
    print(f"Found {len(hospitals)} unique hospitals in schema.sql.")

    print("\nStep 3: Filtering out doctors with missing hospitals...")
    df['HOSPITAL_LOWER'] = df['HOSPITAL / CHAMBER'].apply(lambda x: str(x).lower().strip() if not pd.isna(x) else '')
    
    # Check match percentage
    df['HAS_MATCH'] = df['HOSPITAL_LOWER'].apply(lambda x: x in hospitals)
    matched_df = df[df['HAS_MATCH']].copy()
    missing_hosp_names = df[~df['HAS_MATCH']]['HOSPITAL / CHAMBER'].dropna().unique()
    
    print(f"Skipped {len(df) - len(matched_df)} rows with missing hospitals ({len(missing_hosp_names)} unique missing hospitals).")
    print(f"Kept {len(matched_df)} doctor records associated with existing hospitals.")

    print("\nStep 4: Generating clean credentials and formatting columns...")
    new_users = []
    new_doc_details = []
    
    start_user_id = 12
    default_password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' # 'password'
    
    for i, row in matched_df.reset_index(drop=True).iterrows():
        user_id = start_user_id + i
        
        # 1. Clean Name
        raw_name = str(row['DOCTOR NAME']).strip()
        if not raw_name.startswith("Dr.") and not raw_name.startswith("Prof.") and not raw_name.startswith("Assoc."):
            raw_name = "Dr. " + raw_name
            
        # 2. Unique Email Generation
        # Convert name to lowercase alphanumeric words separated by dots
        clean_name = "".join(c for c in raw_name if c.isalnum() or c.isspace()).strip().lower()
        clean_name = ".".join(clean_name.split())
        if not clean_name.startswith("dr."):
            clean_name = "dr." + clean_name
        email = f"{clean_name}.{user_id}@mpes.com"
        
        # 3. Phone (Hotline)
        phone = str(row['HOTLINE']).strip() if not pd.isna(row['HOTLINE']) else ''
        # If it has decimal like 9610010615.0, clean it
        if phone.endswith('.0'):
            phone = phone[:-2]
        
        # 4. Address (Location)
        address = str(row['LOCATION']).strip() if not pd.isna(row['LOCATION']) else ''
        
        # 5. Specialization
        specialist = clean_specialization(row['SPECIALIZATION'])
        
        # 6. Experience Years
        exp_raw = str(row['EXPERIENCE']).strip() if not pd.isna(row['EXPERIENCE']) else ''
        exp_match = re.search(r'\d+', exp_raw)
        experience_years = int(exp_match.group(0)) if exp_match else 5
        
        # 7. Hospital ID
        hosp_name = str(row['HOSPITAL / CHAMBER']).lower().strip()
        hospital_id = hospitals[hosp_name]
        
        # 8. Consultation Fee
        fee_raw = str(row['FEE (BDT)']).strip() if not pd.isna(row['FEE (BDT)']) else ''
        fee_match = re.search(r'\d+', fee_raw)
        consultation_fee = float(fee_match.group(0)) if fee_match else 500.00
        
        # 9. Bio
        bio = f"Professional {specialist} with {experience_years} years of dedicated experience. Committed to providing premium healthcare and exceptional patient clinical support."
        
        # 10. License Number
        license_number = f"MED-2026-{user_id:05d}"
        
        # 11. Random Aesthetic Rating & Reviews
        rating = round(random.uniform(4.50, 4.95), 2)
        total_reviews = random.randint(15, 180)
        
        # Add to lists
        new_users.append((
            user_id,
            raw_name,
            email,
            default_password_hash,
            'doctor',
            phone if phone else None,
            address if address else None,
            'approved'
        ))
        
        new_doc_details.append((
            user_id,
            license_number,
            specialist,
            experience_years,
            hospital_id,
            consultation_fee,
            rating,
            total_reviews,
            True, # available
            bio
        ))

    print(f"Generated {len(new_users)} users and doctor_details records.")

    print("\nStep 5: Writing SQL Insert Statements in chunks...")
    users_sql = []
    doc_details_sql = []
    
    chunk_size = 500
    
    # Generate Users SQL chunks
    for i in range(0, len(new_users), chunk_size):
        chunk = new_users[i:i+chunk_size]
        sql_lines = []
        sql_lines.append("INSERT INTO users (id, name, email, password, role, phone, address, status) VALUES")
        for j, u in enumerate(chunk):
            phone_val = escape_sql(u[5])
            addr_val = escape_sql(u[6])
            comma = "," if j < len(chunk) - 1 else ";"
            name_val = u[1].replace("'", "''")
            sql_lines.append(f"    ({u[0]}, '{name_val}', '{u[2]}', '{u[3]}', '{u[4]}', {phone_val}, {addr_val}, '{u[7]}'){comma}")
        users_sql.append("\n".join(sql_lines))

    # Generate Doctor Details SQL chunks
    for i in range(0, len(new_doc_details), chunk_size):
        chunk = new_doc_details[i:i+chunk_size]
        sql_lines = []
        sql_lines.append("INSERT INTO doctor_details (user_id, license_number, specialist, experience_years, hospital_id, consultation_fee, rating, total_reviews, available, bio) VALUES")
        for j, d in enumerate(chunk):
            comma = "," if j < len(chunk) - 1 else ";"
            bio_val = d[9].replace("'", "''")
            sql_lines.append(f"    ({d[0]}, '{d[1]}', '{d[2]}', {d[3]}, {d[4]}, {d[5]:.2f}, {d[6]:.2f}, {d[7]}, TRUE, '{bio_val}'){comma}")
        doc_details_sql.append("\n".join(sql_lines))

    all_users_insert_str = "\n\n-- ── Additional Imported Users ───────────────────────────────\n" + "\n\n".join(users_sql)
    all_docs_insert_str = "\n\n-- ── Additional Imported Doctor Details ───────────────────────\n" + "\n\n".join(doc_details_sql)

    print("\nStep 6: Injecting additional inserts into schema.sql...")
    user_target = "('Dr. Aisha Raza',   'aisha.raza@mpes.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor',  NULL,  '+1-555-1009');"
    doc_target = "    (11, 'MED-2026-009', 'Ophthalmologist',  13, 1, 75.00,  4.80, 172, TRUE,  'Experienced ophthalmologist specializing in cataract surgery and glaucoma management.');"

    if user_target not in schema_content:
        raise ValueError("Could not find target user insertion line in schema.sql!")
    if doc_target not in schema_content:
        raise ValueError("Could not find target doctor_details insertion line in schema.sql!")

    # Inject users
    schema_content = schema_content.replace(user_target, user_target + "\n" + all_users_insert_str)
    # Inject doctor details
    schema_content = schema_content.replace(doc_target, doc_target + "\n" + all_docs_insert_str)

    # Write updated schema.sql back
    with open('sql/schema.sql', 'w', encoding='utf-8') as f:
        f.write(schema_content)

    print("\nSUCCESS! Successfully processed Excel data, cleaned specializations, removed missing hospitals, and successfully seeded schema.sql.")

if __name__ == '__main__':
    main()
