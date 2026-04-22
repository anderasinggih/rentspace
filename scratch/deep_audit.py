import re
import sys

def comprehensive_audit(filepath):
    with open(filepath, 'r') as f:
        content = f.read()
    
    stack = []
    tokens = re.finditer(r'<div|</div|@if|@endif|@foreach|@endforeach', content)
    
    def get_line_col(pos):
        line = content.count('\n', 0, pos) + 1
        return line

    for match in tokens:
        token = match.group()
        line = get_line_col(match.start())
        
        if token in ['<div', '@if', '@foreach']:
            stack.append((token, line))
        else:
            if not stack:
                print(f"EXTRA {token} at line {line}")
                continue
            
            last_token, last_line = stack.pop()
            
            if token == '</div' and last_token != '<div':
                print(f"MISMATCH at {line}: {token} closed {last_token} from {last_line}")
            elif token == '@endif' and last_token != '@if':
                print(f"MISMATCH at {line}: {token} closed {last_token} from {last_line}")
            elif token == '@endforeach' and last_token != '@foreach':
                print(f"MISMATCH at {line}: {token} closed {last_token} from {last_line}")

    if stack:
        print("\nUNCLOSED:")
        for token, line in stack:
            print(f"  {token} from line {line}")

if len(sys.argv) > 1:
    comprehensive_audit(sys.argv[1])
else:
    comprehensive_audit('resources/views/livewire/admin/monitoring.blade.php')
