---
title: "Fixing \"Invalid Pot Size\" Errors When Importing CoinPoker Hands"
slug: fix-invalid-pot-size-error-coinpoker-import
excerpt: "Almost every 'Invalid Pot Size' error on a CoinPoker import traces back to one specific summary-line format. Here's how to fix it."
meta_title: "Fix \"Invalid Pot Size\" Errors on CoinPoker Imports"
meta_description: "Why PokerTracker 4 rejects CoinPoker hands with an \"Invalid Pot Size\" error, and how the Splash Fee summary line format causes it."
published: true
---

## The single most common CoinPoker import error

If PokerTracker 4 rejects a CoinPoker hand with something like *"Invalid pot size"* — and it's happening on a large share of your hands, not just one or two — there's a specific, well-understood cause: a newer CoinPoker summary-line format that includes a **Splash Fee** alongside the rake.

## What's actually in the file

A normal CoinPoker summary line looks like:

```
Total pot ₮3.67 | Rake ₮0.18
```

Some cash tables now add a Splash Fee on top:

```
Total pot ₮3.67 | Rake ₮0.18 | Splash Fee ₮0.02
```

PokerTracker 4 has no concept of a "Splash Fee" — and its presence in the line doesn't just get ignored, it stops PT4 from reading the `Rake` figure at all. PT4 then computes rake as `0`, checks that against the pot it calculated independently from the betting action, finds a mismatch, and rejects the hand as an invalid pot.

## Why it looks stake-specific (but isn't)

Because the Splash Fee shows up more often on certain tables, this can look like a stakes-specific bug at first — "my NL10 hands are fine but NL2 keeps failing." It isn't tied to a stake; it's tied to whichever tables happen to charge that fee. The fix has to apply uniformly, regardless of stake, or you'll just see the same failure resurface on a different table later.

## The fix

[PokerHandsConverter](/) detects the Splash Fee pattern and folds it into the rake figure PokerTracker 4 already understands, so the line above becomes:

```
Total pot $3.67 | Rake $0.20
```

That's the rake PT4's own independent calculation expects — pot equals collected amounts plus rake, balanced, no mismatch, no rejected hand. If you're seeing this error on a file today, re-running it through the converter resolves it without touching anything in PokerTracker 4 itself.

## If you're still seeing pot-size errors after converting

A few other things produce a similar-looking error and are worth ruling out:

- **Run-it-twice boards.** These get flagged as warnings during conversion rather than guessed at silently — check your conversion history for a warning on the specific hand.
- **A genuinely corrupted export.** Rare, but if CoinPoker's own export was truncated mid-file, no converter can reconstruct the missing data.
