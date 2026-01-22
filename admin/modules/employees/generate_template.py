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

        # 3. Sheet 2: MA_THAM_CHIEU
        depts = data_source.get('departments', [])
        pos = data_source.get('positions', [])
        projs = data_source.get('projects', [])
        
        # Prepare Position Data: "Dept Name - Pos Name"
        pos_display = []
        pos_codes = []
        pos_dept_codes = []
        
        for p in pos:
            d_name = p.get('dept_name')
            d_code = p.get('dept_code')
            p_name = p.get('name')
            p_code = p.get('code')
            
            if d_name:
                display_name = f"{p_name} ({d_name})"
            else:
                display_name = p_name
                d_code = "-"
            
            pos_display.append(display_name)
            pos_codes.append(p_code)
            pos_dept_codes.append(d_code)

        # Find max length to pad columns
        max_len = max(len(depts), len(pos), len(projs))
        
        def pad(lst, length, filler=''):
            return lst + [filler] * (length - len(lst))

        ref_data = {
            '--- PHÒNG BAN ---': [''] * max_len, # Header grouping
            'MÃ PB': pad([x['code'] for x in depts], max_len),
            'TÊN PB': pad([x['name'] for x in depts], max_len),
            
            '||': [''] * max_len, # Spacer
            
            '--- CHỨC VỤ (THEO PHÒNG) ---': [''] * max_len,
            'THUỘC MÃ PB': pad(pos_dept_codes, max_len), # New column to help lookup
            'MÃ CV': pad(pos_codes, max_len),
            'TÊN CHỨC VỤ': pad(pos_display, max_len),
            
            '|||': [''] * max_len, # Spacer
            
            '--- DỰ ÁN ---': [''] * max_len,
            'MÃ DA': pad([x['code'] for x in projs], max_len),
            'TÊN DỰ ÁN': pad([x['name'] for x in projs], max_len),
        }
        
        df_ref = pd.DataFrame(ref_data)

        # 4. Write to Excel
        with pd.ExcelWriter(output_file_path, engine='openpyxl') as writer:
            df_input.to_excel(writer, sheet_name='NHAP_LIEU', index=False)
            df_ref.to_excel(writer, sheet_name='MA_THAM_CHIEU', index=False)
            
            # Auto-adjust column width
            worksheet = writer.sheets['MA_THAM_CHIEU']
            for idx, col in enumerate(df_ref.columns):
                # Calculate max length of data in column
                max_len = 0
                for cell in worksheet[chr(65 + idx)]:
                    if cell.value:
                        max_len = max(max_len, len(str(cell.value)))
                
                # Set width (limit to 50 chars)
                adjusted_width = min(max_len + 2, 50)
                worksheet.column_dimensions[chr(65 + idx)].width = adjusted_width

        print(json.dumps({"status": "success", "path": output_file_path}))

    except Exception as e:
        print(json.dumps({"status": "error", "message": str(e)}))

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({"status": "error", "message": "Missing arguments"}))
    else:
        generate_template(sys.argv[1], sys.argv[2])