import pandas as pd
import sys
import json
import datetime

def parse_excel(file_path):
    try:
        xl = pd.ExcelFile(file_path)
    except Exception as e:
        print(json.dumps({"error": f"Cannot open file: {str(e)}"}))
        return

    all_data = []

    # Các sheet cần bỏ qua (không phải chấm công)
    ignored_sheets = ['MỨC LƯƠNG KHOÁN', 'phieuung', 'QUA', 'Sheet3', 'The bao hiem']

    for sheet_name in xl.sheet_names:
        if sheet_name in ignored_sheets or 'Sheet' in sheet_name:
            continue
        
        # Đọc sheet
        df = pd.read_excel(file_path, sheet_name=sheet_name, header=None)
        
        # 1. Tìm dòng Header (chứa ngày 1, 2, 3... hoặc ngày tháng)
        # Thông thường ở dòng 7 (index 6) hoặc 8 (index 7)
        header_row_idx = -1
        # Tìm dòng có chứa số 1, 2, 3 liên tiếp ở các cột giữa
        for i in range(15): # Quét 15 dòng đầu
            row_values = df.iloc[i].tolist()
            # Đếm xem có bao nhiêu số nguyên từ 1-31
            count_days = sum(1 for x in row_values if isinstance(x, (int, float)) and 1 <= x <= 31)
            if count_days > 20: # Nếu tìm thấy > 20 ngày -> Đây là dòng header ngày
                header_row_idx = i
                break
        
        if header_row_idx == -1:
            continue

        # Xác định range ngày tháng (cột bắt đầu và kết thúc)
        # Thường bắt đầu từ cột 4 (index 3)
        day_cols = {} # map col_index -> day_number
        row_values = df.iloc[header_row_idx].tolist()
        for col_idx, val in enumerate(row_values):
            if isinstance(val, (int, float)) and 1 <= val <= 31:
                day_cols[col_idx] = int(val)
        
        if not day_cols:
            continue

        # Xác định cột Họ tên (thường là col 1 hoặc 2)
        name_col_idx = -1
        # Tìm dòng tiêu đề chữ (STT, Họ tên...) thường nằm trên dòng ngày 1-2 dòng
        title_row = df.iloc[header_row_idx - 1].tolist()
        for idx, val in enumerate(title_row):
            if isinstance(val, str) and ('HỌ VÀ TÊN' in val.upper() or 'HỌ TÊN' in val.upper()):
                name_col_idx = idx
                break
        
        if name_col_idx == -1: 
             # Fallback: Cột 1 hoặc 2
             name_col_idx = 1 if len(df.columns) > 1 else 0

        # Lặp qua dữ liệu nhân viên (từ dòng header + 1)
        i = header_row_idx + 1
        while i < len(df):
            # Kiểm tra dòng này có phải nhân viên không?
            # Thường cột STT (trước cột Tên) sẽ có số
            stt_val = df.iloc[i, name_col_idx - 1] if name_col_idx > 0 else None
            name_val = df.iloc[i, name_col_idx]

            # Logic xác định nhân viên: Có tên và (có STT hoặc STT là số)
            is_employee = False
            if isinstance(name_val, str) and len(name_val.strip()) > 2:
                # Loại bỏ các dòng tổng cộng, chữ ký...
                if 'CỘNG' in name_val.upper() or 'KÝ TÊN' in name_val.upper():
                    i += 1
                    continue
                is_employee = True
            
            if is_employee:
                employee_data = {
                    'sheet': sheet_name,
                    'name': name_val.strip(),
                    'attendance': {}
                }

                # Dòng 1: Ký hiệu (Symbol)
                for col_idx, day_num in day_cols.items():
                    symbol = df.iloc[i, col_idx]
                    if pd.isna(symbol): symbol = ''
                    symbol = str(symbol).strip()
                    if symbol:
                        employee_data['attendance'][day_num] = {
                            'symbol': symbol,
                            'ot': 0
                        }

                # Kiểm tra dòng kế tiếp (Dòng OT)
                # Dòng OT thường không có tên, hoặc tên rỗng
                next_row_idx = i + 1
                if next_row_idx < len(df):
                    next_name_val = df.iloc[next_row_idx, name_col_idx]
                    # Nếu dòng dưới ko có tên -> Là dòng OT của nhân viên trên
                    if pd.isna(next_name_val) or str(next_name_val).strip() == '':
                        for col_idx, day_num in day_cols.items():
                            ot_val = df.iloc[next_row_idx, col_idx]
                            if pd.notna(ot_val) and isinstance(ot_val, (int, float)) and ot_val > 0:
                                if day_num not in employee_data['attendance']:
                                     employee_data['attendance'][day_num] = {'symbol': '', 'ot': 0}
                                employee_data['attendance'][day_num]['ot'] = float(ot_val)
                        i += 1 # Skip dòng OT

                all_data.append(employee_data)
            
            i += 1

    print(json.dumps(all_data, ensure_ascii=False))

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No file path provided"}))
    else:
        parse_excel(sys.argv[1])
