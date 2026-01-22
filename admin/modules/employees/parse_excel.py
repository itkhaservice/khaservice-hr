import pandas as pd
import sys
import json
import datetime

def clean_val(val):
    if pd.isna(val):
        return ""
    s = str(val).strip()
    if s == '-':
        return ""
    return s

def parse_employee_excel(file_path):
    try:
        # Read specifically the first sheet (index 0)
        df = pd.read_excel(file_path, sheet_name=0, header=0)
    except Exception as e:
        print(json.dumps({"error": f"Cannot open file: {str(e)}"}))
        return

    employees = []
    
    # Normalize column names
    df.columns = df.columns.astype(str).str.strip().str.lower()
    
    # Map columns
    col_map = {
        'fullname': ['họ và tên', 'họ tên', 'fullname', 'name'],
        'gender': ['giới tính', 'sex', 'gender'],
        'dob': ['ngày sinh', 'dob', 'birthdate'],
        'phone': ['sđt', 'số điện thoại', 'phone'],
        'email': ['email'],
        'identity_card': ['cccd', 'cmnd', 'id card', 'số cccd'],
        'dept_code': ['mã phòng ban', 'mã ban', 'mã bộ phận', 'dept code'],
        'pos_code': ['mã chức vụ', 'mã vị trí', 'pos code'],
        'proj_code': ['mã dự án', 'mã da', 'project code'],
        'start_date': ['ngày bắt đầu', 'ngày vào làm', 'start date']
    }
    
    actual_cols = {}
    for target, keywords in col_map.items():
        found = None
        for col in df.columns:
            if col in keywords:
                found = col
                break
        actual_cols[target] = found

    # Iterate
    for index, row in df.iterrows():
        name_col = actual_cols['fullname']
        if not name_col or pd.isna(row[name_col]):
            continue
            
        emp_data = {}
        emp_data['fullname'] = str(row[name_col]).strip()
        
        if actual_cols['gender']:
            g_raw = clean_val(row[actual_cols['gender']]).lower()
            if 'nam' in g_raw or 'male' in g_raw:
                emp_data['gender'] = 'Nam'
            elif 'nữ' in g_raw or 'female' in g_raw:
                emp_data['gender'] = 'Nữ'
            else:
                emp_data['gender'] = 'Khác'
        else:
            emp_data['gender'] = ''

        emp_data['dob'] = clean_val(row[actual_cols['dob']]) if actual_cols['dob'] else ""
        if actual_cols['dob'] and isinstance(row[actual_cols['dob']], datetime.datetime):
             emp_data['dob'] = row[actual_cols['dob']].strftime('%Y-%m-%d')

        emp_data['phone'] = clean_val(row[actual_cols['phone']]) if actual_cols['phone'] else ""
        emp_data['email'] = clean_val(row[actual_cols['email']]) if actual_cols['email'] else ""
        emp_data['identity_card'] = clean_val(row[actual_cols['identity_card']]) if actual_cols['identity_card'] else ""
        
        emp_data['dept_code'] = clean_val(row[actual_cols['dept_code']]) if actual_cols['dept_code'] else ""
        emp_data['pos_code'] = clean_val(row[actual_cols['pos_code']]) if actual_cols['pos_code'] else ""
        emp_data['proj_code'] = clean_val(row[actual_cols['proj_code']]) if actual_cols['proj_code'] else ""

        if actual_cols['start_date'] and pd.notna(row[actual_cols['start_date']]):
            val = row[actual_cols['start_date']]
            if isinstance(val, datetime.datetime):
                emp_data['start_date'] = val.strftime('%Y-%m-%d')
            else:
                emp_data['start_date'] = clean_val(val)
        else:
            emp_data['start_date'] = ""

        employees.append(emp_data)

    print(json.dumps(employees, ensure_ascii=False))

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No file path provided"}))
    else:
        parse_employee_excel(sys.argv[1])
