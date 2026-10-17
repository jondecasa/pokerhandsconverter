---
title: "How to Export Your Hand History from CoinPoker (Step by Step)"
slug: how-to-export-hand-history-coinpoker
excerpt: "CoinPoker doesn't save hand history files on your computer as you play. Here's where to request them, what you get back, and what to do next."
meta_title: "How to Export Hand Histories from CoinPoker — Step by Step"
meta_description: "Step-by-step guide to downloading your CoinPoker hand history from PokerIntel: cash games or tournaments, a date range, and the emailed download link."
published: true
---

## Why you have to request your hands

Most poker rooms write each hand to a text file on your computer while you play, and your tracker reads it from there. CoinPoker doesn't work that way: it doesn't write real-time hand history files, so there is nothing to find in a folder on your PC. Instead you **request a download** of the hands you've already played and import them afterwards, for review rather than live.

The request happens in **PokerIntel**, CoinPoker's stats area, and it takes a few minutes.

## Before you start

- The download is **requested, then emailed**. CoinPoker sends the link to the email address registered on your account, so make sure you can still open that inbox.
- **Cash games and tournaments are exported separately.** You pick one, request it, and repeat for the other.
- CoinPoker doesn't create separate tournament summary files, so a tournament download contains the hand histories only.
- You choose **Lifetime** (every hand you've played) or **Custom** (a date range).

## Step by step

1. **Open the CoinPoker lobby** and click the arrow at the top right, next to the wallet icon.
2. Choose **PokerIntel**.
3. Open **Game History** and pick **Cash Games** or **Tournaments**.
4. Click **Download Hands** at the top right.
5. Choose **Lifetime** to get everything, or **Custom** to pick a date range.
6. Click **Download**.
7. **Wait a few minutes.** CoinPoker emails a download link to your registered address (check your spam folder if it doesn't show up).
8. Open the link and **save the file** somewhere you'll find it again. If it arrives compressed, extract it first.

Menu names and positions can change between versions of the app, so if something looks different, look for **PokerIntel** and **Game History** rather than the exact icons.

## Tips for regular exports

- **Use a custom range after your first export.** Download only what's new since last time, so the files stay small and you don't re-import hands you already have.
- **Keep the files in one folder**, named by date range, so you always know what you've imported.
- **Request cash games and tournaments separately** and keep them apart, since trackers treat them differently.

## What about a live HUD?

Because CoinPoker doesn't write real-time hand histories, there is no live feed for a tracker to read while you play. The workflow is: play your session, request the hands, import them, and study your stats afterwards. If you want to understand what those numbers mean once they're in your tracker, [understanding VPIP, PFR and 3-bet%](/blog/understanding-vpip-pfr-3bet-hud-stats) is a good place to start.

## The file isn't ready for your tracker yet

What you download is in CoinPoker's own dialect, which is close to the PokerStars-style format trackers expect but not the same: the header, the currency sign, the player lines and the timestamps all differ. That's why importing the raw file often fails or produces wrong stats.

[PokerHandsConverter](/) rewrites the file into a format PokerTracker 4 and similar trackers read, in a few seconds. If you've already tried to import and hit an "Invalid pot size" error, [this fix](/blog/fix-invalid-pot-size-error-coinpoker-import) explains the cause. And if you haven't picked a tracker yet, [PokerTracker 4 vs Hold'em Manager 3 for CoinPoker](/blog/pokertracker-4-vs-holdem-manager-3-coinpoker) compares the two.

## If something goes wrong

- **The email never arrives.** Check spam, confirm it's the address on your CoinPoker account, and give it more than a few minutes. If it still doesn't come, request it again or contact CoinPoker support.
- **The file is empty or tiny.** The date range probably contains no hands for that game type. Try Lifetime, or switch between Cash Games and Tournaments.
- **You only see one type of game.** You downloaded Cash Games or Tournaments, not both. Run the request again for the other one.
