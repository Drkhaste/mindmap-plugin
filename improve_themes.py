import os

filepath = 'mind-map-studio/mind-map-studio/assets/css/jsmind.css'
with open(filepath, 'r', encoding='utf-8') as f:
    lines = f.readlines()

new_lines = []
for line in lines:
    if 'box-shadow: 1px 1px 1px #666;' in line:
        new_lines.append(line.replace('box-shadow: 1px 1px 1px #666;', 'box-shadow: 0 2px 5px rgba(0,0,0,0.1);'))
    elif 'box-shadow: 2px 2px 8px #000;' in line:
        new_lines.append(line.replace('box-shadow: 2px 2px 8px #000;', 'box-shadow: 0 4px 12px rgba(0,0,0,0.15);'))
    else:
        new_lines.append(line)

with open(filepath, 'w', encoding='utf-8') as f:
    f.writelines(new_lines)
