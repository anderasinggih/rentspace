
def trace_tags(filepath):
    with open(filepath, 'r') as f:
        lines = f.readlines()
    
    import re
    tag_regex = re.compile(r'<div|</div>')
    
    stack = []
    issues = []
    
    for i, line in enumerate(lines):
        line_num = i + 1
        matches = tag_regex.findall(line)
        for match in matches:
            if match == '<div':
                stack.append(line_num)
            elif match == '</div>':
                if not stack:
                    issues.append(f"Extra </div> at line {line_num}")
                else:
                    stack.pop()
    
    for open_line in stack:
        issues.append(f"Unclosed <div> from line {open_line}")
    
    if not issues:
        print("All <div> tags are balanced.")
    else:
        for issue in issues:
            print(issue)

trace_tags('resources/views/livewire/admin/monitoring.blade.php')
