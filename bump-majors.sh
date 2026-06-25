#!/bin/bash
set -e

echo "Checking for major version updates..."

eval "$(composer outdated --major-only --direct --format=json 2>/dev/null | python3 -c "
import sys, json

data = json.load(sys.stdin)

with open('composer.json', 'r') as f:
    composer = json.load(f)

dev_packages = list(composer.get('require-dev', {}).keys())

prod = []
dev = []

for pkg in data.get('installed', []):
    name = pkg['name']
    latest = pkg['latest'].lstrip('v')
    parts = latest.split('.')
    if parts[0] == '0':
        constraint = f'^{parts[0]}.{parts[1]}'
    else:
        constraint = f'^{parts[0]}.0'

    if name in dev_packages:
        dev.append(f'{name}:{constraint}')
        print(f'  [dev] {name}: {pkg[\"version\"]} -> {latest} ({constraint})', file=sys.stderr)
    else:
        prod.append(f'{name}:{constraint}')
        print(f'  [prod] {name}: {pkg[\"version\"]} -> {latest} ({constraint})', file=sys.stderr)

print(f'PROD_PACKAGES=\"{\" \".join(prod)}\"')
print(f'DEV_PACKAGES=\"{\" \".join(dev)}\"')
")"

if [ -z "$PROD_PACKAGES" ] && [ -z "$DEV_PACKAGES" ]; then
    echo "Everything is up to date!"
    exit 0
fi

echo ""
read -p "Continue? (y/n) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    if [ -n "$DEV_PACKAGES" ]; then
        echo "Bumping dev: $DEV_PACKAGES"
        composer require $DEV_PACKAGES --dev -W
    fi
    if [ -n "$PROD_PACKAGES" ]; then
        echo "Bumping prod: $PROD_PACKAGES"
        composer require $PROD_PACKAGES -W
    fi
else
    echo "Aborted."
fi
