# Implementační dokumentace k 2. úloze do IPP 2023/2024

**Jméno a příjmení:** Lukáš Tesař \
**Login:** xtesar43

## Implementation - core fundamentals

## Design decicions and caveats
- One of the caveats was how to represent `undefined` variable value. I first tried to represent it as a `nil@<nothing>`, but it turned out it can be conflicting with normal nil type in some cases, so I then used special type `undefined@<nothing>` instead.
