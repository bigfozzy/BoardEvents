---
name: task-planning-pipeline
version: 1.0.0
author: system
description: Pipeline for structured task planning and execution
---

# task-planning-pipeline

## Pipeline Overview

This rule defines the standard pipeline for planning and executing tasks based on the task-planner skill.

## Pipeline Stages

### Stage 1: Request Analysis
1. Identify trigger phrases ("спланируй", "создай план", "plan", "create plan", "generate plan")
2. Extract task description
3. Determine main goal and scope
4. Identify files to be processed
5. Determine required operations

### Stage 2: File Identification
1. List all target files with full paths
2. Number files sequentially
3. Group related files together
4. Collect file stats if available
5. Establish file dependencies

### Stage 3: Step Generation
1. Create specific, actionable steps
2. Group similar operations together
3. Provide concrete examples where needed
4. Ensure each step is measurable

### Stage 4: Guidelines Definition
1. Define translation rules (if applicable)
2. Define preservation rules
3. Create do/don't lists
4. Specify formatting requirements

### Stage 5: Execution Planning
1. Define expected outcomes
2. Establish success metrics
3. Determine execution order
4. Process from complex to simple or by dependency

### Stage 6: Plan Creation
1. Generate plan in Markdown format
2. Save to `PLAN.md` in the project root
3. Use filename pattern: `[Unix_timestamp]_[kebab-case-name].md`
4. Follow standard plan structure

## Plan Template

```markdown
# Plan: [Title]

## Overview
[Brief description of what the plan accomplishes]

## Files to Process ([number] files)

1. `[full path to file 1]`
2. `[full path to file 2]`

## Steps

### Step 1: [Descriptive name]
- [Action 1]
- [Action 2]
- Example: [concrete example]

### Step 2-N: [Descriptive name]
For each file:
1. **[Category of action]**
   - [Specific action]

2. **[Category of action]**
   - [Specific action]

## [Specific Guidelines]

[Category 1]:
- [Item 1]
- [Item 2]

[Category 2]:
- [Item 1]
- [Item 2]

## Expected Outcome

- [Result 1]
- [Result 2]
- [Result 3]

## Execution Order

Process files in this order:
1. [File] ([stats])
2. [File] ([stats])
```

## Quality Standards

1. **Simplicity**: Avoid unnecessary metadata
2. **Specificity**: Use concrete examples
3. **Logical Organization**: Group related actions
4. **Clarity**: Use clear headings and numbered lists
5. **Context**: Explain why steps are needed
6. **Accessibility**: Avoid technical jargon

## Validation Criteria

- All files are listed with full paths
- Each step is specific and actionable
- Guidelines are clearly defined
- Expected outcomes are measurable
- Execution order is specified
- Plan is saved with correct filename pattern