/* GLIB - Library of useful routines for C programming
 * Copyright (C) 1995-1997, 1999  Peter Mattis, Red Hat, Inc.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place - Suite 330,
 * Boston, MA 02111-1307, USA.
 */
#ifndef __G_PATTERN_H__
#define __G_PATTERN_H__

//#include <glib/gtypes.h>

#include <stdlib.h>

//G_BEGIN_DECLS

// REC

#define gchar char
#define gboolean int
#define guint unsigned long
#define gint long

#ifndef inline
	#define inline __inline
#endif

#ifndef FALSE
	#define FALSE 0
#endif
#ifndef TRUE
	#define TRUE -1
#endif

#define g_utf8_next_char(a) ((a) + 1)
#define g_return_val_if_fail(a, b) if (!(a)) return (b)
#define g_return_if_fail(a) if (!(a)) return

#define g_new(struct_type, n_structs)		\
    ((struct_type *) malloc(sizeof(struct_type) * (n_structs)))
#define g_free(p) free(p);

// /REC

typedef struct _GPatternSpec    GPatternSpec;

GPatternSpec* g_pattern_spec_new       (const gchar  *pattern);
void          g_pattern_spec_free      (GPatternSpec *pspec);
gboolean      g_pattern_spec_equal     (GPatternSpec *pspec1,
					GPatternSpec *pspec2);
gboolean      g_pattern_match          (GPatternSpec *pspec,
					guint         string_length,
					const gchar  *string,
					const gchar  *string_reversed);
gboolean      g_pattern_match_string   (GPatternSpec *pspec,
					const gchar  *string);
gboolean      g_pattern_match_simple   (const gchar  *pattern,
					const gchar  *string);

//G_END_DECLS

#endif /* __G_PATTERN_H__ */