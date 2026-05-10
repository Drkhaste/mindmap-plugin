import os

filepath = 'mind-map-studio/mind-map-studio/inc/class-mind-map-studio.php'
with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace(
    "<option value=\"straight\" <?php selected( get_option( 'mind_map_line_style', 'bezier' ), 'straight' ); ?>",
    "<option value=\"straight\" <?php selected( get_option( 'mind_map_line_style', 'bezier' ), 'straight' ); ?>"
)
content = content.replace(
    "<option value=\"rounded\" <?php selected( get_option( 'mind_map_line_style', 'bezier' ), 'rounded' ); ?>",
    "<option value=\"rounded\" <?php selected( get_option( 'mind_map_line_style', 'bezier' ), 'rounded' ); ?>"
)

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(content)
