import pandas as pd
import sys
import json
import os

def generate_template(json_file_path, output_file_path):
    try:
        # 1. Read Data
        with open(json_file_path, 'r', encoding='utf-8') as f:
            data_source = json.load(f)
        
        # 2. Sheet 1: NHAP_LIEU
        input_data = {
            'Họ và Tên': ['Nguyễn Văn A'],
            'Giới tính': ['Nam'],
            'Ngày sinh': ['1990-01-01'],
            'SĐT': ['090xxxxxxx'],
            'Email': ['email@example.com'],
            'Số CCCD': ['079xxxxxxxxx'],
            'Mã Phòng ban': ['DEPT...'],
            'Mã Chức vụ': ['POS...'],
            'Mã dự án': ['PROJ...'],
            'Ngày bắt đầu làm việc': ['']
        }
        df_input = pd.DataFrame(input_data)

        # 3. Sheet 2: MA_THAM_CHIEU - Clean Implementation
        depts = data_source.get('departments', [])
        pos = data_source.get('positions', [])
        projs = data_source.get('projects', [])
        
        # Prepare DataFrames
        df_depts = pd.DataFrame({
            'MÃ PB': [x.get('code', '') for x in depts],
            'TÊN PHÒNG BAN': [x.get('name', '') for x in depts]
        })
        
        df_pos = pd.DataFrame({
            'MÃ CV': [x.get('code', '') for x in pos],
            'TÊN CHỨC VỤ': [x.get('name', '') for x in pos],
            'THUỘC PHÒNG BAN': [x.get('dept_name', '') for x in pos]
        })
        
        df_projs = pd.DataFrame({
            'MÃ DA': [x.get('code', '') for x in projs],
            'TÊN DỰ ÁN': [x.get('name', '') for x in projs]
        })

        # 4. Write to Excel with styling
        with pd.ExcelWriter(output_file_path, engine='openpyxl') as writer:
            # Sheet 1: Input Template
            df_input.to_excel(writer, sheet_name='NHAP_LIEU', index=False)
            
            # Sheet 2: References - Write side by side
            df_depts.to_excel(writer, sheet_name='MA_THAM_CHIEU', index=False, startcol=0) # A-B
            df_pos.to_excel(writer, sheet_name='MA_THAM_CHIEU', index=False, startcol=3)   # D-F
            df_projs.to_excel(writer, sheet_name='MA_THAM_CHIEU', index=False, startcol=7) # H-I
            
            # Formatting
            workbook = writer.book
            ws_ref = writer.sheets['MA_THAM_CHIEU']
            
            # Set Column Widths
            ws_ref.column_dimensions['A'].width = 15
            ws_ref.column_dimensions['B'].width = 30
            ws_ref.column_dimensions['C'].width = 2 # Spacer
            
            ws_ref.column_dimensions['D'].width = 15
            ws_ref.column_dimensions['E'].width = 30
            ws_ref.column_dimensions['F'].width = 25
            ws_ref.column_dimensions['G'].width = 2 # Spacer
            
            ws_ref.column_dimensions['H'].width = 15
            ws_ref.column_dimensions['I'].width = 40

        print(json.dumps({"status": "success", "path": output_file_path}))

    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({"status": "error", "message": "Missing arguments"}))
    else:
        generate_template(sys.argv[1], sys.argv[2])