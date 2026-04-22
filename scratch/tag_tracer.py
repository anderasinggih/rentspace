
import re

def full_trace(filepath):
    with open(filepath, 'r') as f:
        content = f.read()
    
    tokens = re.finditer(r'<div|</div|@if|@elseif|@else|@endif|@foreach|@endforeach', content)
    stack = []
    
    def get_line(pos):
        return content.count('\n', 0, pos) + 1

    print(f"{'Line':<5} | {'Token':<12} | {'Stack Depth':<12} | {'Top of Stack'}")
    print("-" * 60)
    
    for match in tokens:
        token = match.group()
        line = get_line(match.start())
        
        if token in ['<div', '@if', '@foreach']:
            stack.append((token, line))
            depth = len(stack)
            top = stack[-1][0]
            print(f"{line:<5} | {token:<12} | {depth:<12} | {top}")
        elif token in ['@elseif', '@else']:
            # These don't change stack depth but must be inside @if
            top = stack[-1][0] if stack else "EMPTY"
            print(f"{line:<5} | {token:<12} | {len(stack):<12} | {top}")
        else:
            if not stack:
                print(f"{line:<5} | EXTRA {token:<10} | {'0':<12} | EMPTY")
                continue
            
            last_token, last_line = stack.pop()
            depth = len(stack)
            top = stack[-1][0] if stack else "Root"
            
            # Check for correct closing
            if (token == '</div' and last_token != '<div') or \
               (token == '@endif' and last_token != '@if') or \
               (token == '@endforeach' and last_token != '@foreach'):
                print(f"{line:<5} | MISMATCH {token:<7} | {depth:<12} | Closed {last_token} from {last_line}")
            else:
                print(f"{line:<5} | {token:<12} | {depth:<12} | Closed {last_token} from {last_line}")

    if stack:
        print("\nREMAINING OPEN:")
        for token, line in stack:
            print(f"  {token} from line {line}")

full_trace('resources/views/livewire/admin/monitoring.blade.php')
